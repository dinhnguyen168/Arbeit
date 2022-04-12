<template>
  <div id="game-wrapper">
    <div
      v-for="armorial in armorials"
      :id="armorial.background"
      :key="armorial.background"
      :class="backgroundClasses(armorial)"
      @touchstart="startDrag($event, armorial, 'background')"
      @mousedown="startDrag($event, armorial, 'background')"
      @touchend="dragEnd($event, armorial, 'background')"
      @mouseup="dragEnd($event, armorial, 'background')"
    >
      <img :src="require('@/assets/img/' + armorial.background + '.png')" :alt="armorial.background">
    </div>

    <div
        v-for="armorial in armorials"
        :id="armorial.name"
        :key="armorial.name"
        class="armorial_nameplate"
    >
      <img :src="require('@/assets/img/' + armorial.name + '.png')" :alt="armorial.name">
    </div>

    <div
        v-for="armorial in armorials"
        :id="armorial.foreground"
        :key="armorial.foreground"
        :class="foregroundClasses(armorial)"
        @touchstart="startDrag($event, armorial, 'foreground')"
        @mousedown="startDrag($event, armorial, 'foreground')"
        @touchend="dragEnd($event, armorial, 'foreground')"
        @mouseup="dragEnd($event, armorial, 'foreground')"
    >
      <img :src="require('@/assets/img/' + armorial.foreground + '.png')" :alt="armorial.foreground">
    </div>
  </div>
</template>

<script>
import { Armorial, startArmorials} from "@/utils/wappen";
import _ from "lodash";
import {templateBackgroundClasses, templateForegroundClasses, swapPredecessor} from "@/utils/utils";
import { Sound } from '@/utils/sound'

export default {
  name: 'App',
  data() {
    return {
      $dragElement: null,
      $dropElement: null,
      dragItem: null,
      armorials: [],
      placedIds: [],
      draggedIds: [],
      finished: false,
      touchmoveEventListener: (evt) => { this.drag(evt) },
      touchendEventListener: (evt) => { this.dragEnd(evt) },
    }
  },
  created() {
    window.START_TIME = 1.5 // in Sekunden
    window.FINISHED_TIME = 2.5 // in Sekunde

    startArmorials.map(item => {
      const armorial = new Armorial(item.name, `${item.name}_BG`, `${item.name}_FG`, item.predecessorBG, item.predecessorFG)
      armorial[_.camelCase(`${item.predecessorBG}BG`)] = true
      armorial[_.camelCase(`${item.predecessorFG}FG`)] = true
      this.armorials.push(armorial)
    })
  },
  mounted() {
    this.sound = new Sound()
    this.sound.play('start')
    setTimeout(() => {
      this.sound.play('background')
    }, window.START_TIME * 1000)
  },
  methods: {
    startDrag(evt, armorial, type) {
      evt.preventDefault()
      evt.stopPropagation()
      if(!this.finished) {
        // Play sound
        if (type === 'foreground') this.sound.play('grab_foreground')
        else this.sound.play('grab_background')

        this.$dragElement = evt.target
        if (this.$dragElement.nodeName === 'IMG') this.$dragElement = this.$dragElement.parentNode
        this.$dragElement.style.zIndex = '1000'
        const box = this.$dragElement.getBoundingClientRect()
        const c = evt.type.match(/^touch/) ? evt.touches[0] : evt
        let Mx = c.clientX,
            My = c.clientY,
            offsetX = Mx - box.left,
            offsetY = My - box.top
        this.$dragElement.mouseOffset = {x: offsetX, y: offsetY}
        this.dragItem = armorial
        window.addEventListener(evt.type === 'mousedown' ? 'mousemove' : 'touchmove', this.touchmoveEventListener)
        window.addEventListener(evt.type === 'mousedown' ? 'mouseup' : 'touchend', this.touchendEventListener)
      }
    },
    drag(evt) {
      evt.preventDefault()
      evt.stopPropagation()
      if (this.$dragElement && this.$dragElement.mouseOffset) {
        const c = evt.type.match(/^touch/) ? evt.touches[0] : evt
        this.$dragElement.style.left = (c.clientX - this.$dragElement.mouseOffset.x - this.$el.offsetLeft) + 'px'
        this.$dragElement.style.top = (c.clientY - this.$dragElement.mouseOffset.y - this.$el.offsetTop) + 'px'
      }
    },
    dragEnd(evt, dragArmorial, type) {
      if (this.dragItem) {
        const c = evt.type.match(/^touch/) ? evt.changedTouches[0] : evt
        let dropArmorial
        const successor = this.armorials.filter(item => item.checkInside(c, this))[0]
        if(successor) {
          if(type === 'background')
            dropArmorial = this.armorials.find(item => item.predecessorBG === successor.name)
          else
            dropArmorial = this.armorials.find(item => item.predecessorFG === successor.name)
          if (dropArmorial) {
            this.onDrop(evt, dropArmorial, dragArmorial, successor, type)
            return
          }
        }
        this.resetDragElement()
        this.removeEventListeners()
      }
    },
    removeEventListeners() {
      window.removeEventListener('mousemove', this.touchmoveEventListener)
      window.removeEventListener('touchmove', this.touchmoveEventListener)
      window.removeEventListener('mouseup', this.touchendEventListener)
      window.removeEventListener('touchend', this.touchendEventListener)
    },
    /**
     * Das Spiel ist fertig wenn alle name, hintergründe, vordergrüde richtig zugeordnet sind oder es ist bei den
     * 2 "gleichen" hintergründen egal(z.B: "Wappen_v_Schoenberg" und "Wappen_Milckau" haben fast gleich
     * hintergrund, auch für "Wappen_v_Carlowitz" und "Wappen_Vitzthum_v_Appolda_Tannroda". Dann der Hintergrund
     * "Wappen_v_Schoenberg" darf auch für "Wappen_v_Schoenberg" platziert werden und umgekehrt, das gilt auch
     * für "Wappen_v_Carlowitz" und "Wappen_Vitzthum_v_Appolda_Tannroda") und die anderen Wappen richtig
     * zugeordnet sind.
     * */
    checkResult() {
      this.finished =
          this.armorials.every(armorial => {
            return armorial.name === armorial.predecessorBG && armorial.name === armorial.predecessorFG
          })
          || (this.armorials.some(armorial => armorial.name === "Wappen_v_Schoenberg"
                  && armorial.predecessorBG === "Wappen_Milckau"
                  && armorial.name === armorial.predecessorFG)
              &&this.armorials.some(armorial => armorial.name === "Wappen_Milckau"
                  && armorial.predecessorBG === "Wappen_v_Schoenberg"
                  && armorial.name === armorial.predecessorFG)
              &&this.armorials
                  .filter(armorial => armorial.name !=="Wappen_v_Schoenberg"
                      && armorial.name !=="Wappen_Milckau")
                  .every(armorial => {
                    return armorial.name === armorial.predecessorBG && armorial.name === armorial.predecessorFG
                  }))
          || (this.armorials.some(armorial => armorial.name === "Wappen_v_Carlowitz"
                  && armorial.predecessorBG === "Wappen_Vitzthum_v_Appolda_Tannroda"
                  && armorial.name === armorial.predecessorFG)
              &&this.armorials.some(armorial => armorial.name === "Wappen_Vitzthum_v_Appolda_Tannroda"
                  && armorial.predecessorBG === "Wappen_v_Carlowitz"
                  && armorial.name === armorial.predecessorFG)
              &&this.armorials
                  .filter(armorial => armorial.name !=="Wappen_v_Carlowitz" && armorial.name !=="Wappen_Vitzthum_v_Appolda_Tannroda")
                  .every(armorial => {
                    return armorial.name === armorial.predecessorBG && armorial.name === armorial.predecessorFG
                  }))
          || (this.armorials.some(armorial => armorial.name === "Wappen_v_Schoenberg"
                  && armorial.predecessorBG === "Wappen_Milckau"
                  && armorial.name === armorial.predecessorFG)
              &&this.armorials.some(armorial => armorial.name === "Wappen_Milckau"
                  && armorial.predecessorBG === "Wappen_v_Schoenberg"
                  && armorial.name === armorial.predecessorFG)
              &&this.armorials.some(armorial => armorial.name === "Wappen_v_Carlowitz"
                  && armorial.predecessorBG === "Wappen_Vitzthum_v_Appolda_Tannroda"
                  && armorial.name === armorial.predecessorFG)
              &&this.armorials.some(armorial => armorial.name === "Wappen_Vitzthum_v_Appolda_Tannroda"
                  && armorial.predecessorBG === "Wappen_v_Carlowitz"
                  && armorial.name === armorial.predecessorFG)
              &&this.armorials
                  .filter(armorial => armorial.name !=="Wappen_v_Carlowitz"
                      && armorial.name !=="Wappen_Vitzthum_v_Appolda_Tannroda"
                      &&armorial.name !=="Wappen_v_Schoenberg"
                      && armorial.name !=="Wappen_Milckau")
                  .every(armorial => {
                    return armorial.name === armorial.predecessorBG && armorial.name === armorial.predecessorFG
                  }))

      if (this.finished) {
        // play finished sound
        this.sound.play('finished')
        setTimeout(() => {
          this.sound.stop()
          this.sendMessage({method: 'showPage', pageID: 340})
        }, window.FINISHED_TIME * 1000)
      }
    },
    onDrop(evt, dropArmorial, dragArmorial, successor, type) {
      this.removeEventListeners()
      if (this.dragItem) {
        if (!this.finished) {
          this.placeItem(dropArmorial, dragArmorial, successor, type)
        } else {
          this.resetDragElement(type)
        }
        this.$dragElement = null
        this.dragItem = null
      }
    },
    placeItem(dropArmorial, dragArmorial, successor, type) {
      //play drop sound
      this.sound.play('drop')

      let dragElement, dropElement
      if(type === 'background') {
        dragElement = this.$el.querySelector(`#${dragArmorial.background}`)
        dropElement = this.$el.querySelector(`#${dropArmorial.background}`)
      } else {
        dragElement = this.$el.querySelector(`#${dragArmorial.foreground}`)
        dropElement = this.$el.querySelector(`#${dropArmorial.foreground}`)
      }

      const dragImg = dragElement.querySelector('img')
      const dropImg = dropElement.querySelector('img')
      dragImg.onload = () => {
        if(dragElement && dropElement) {
          dragElement.style.left = null
          dragElement.style.top = null
          dragElement.style.zIndex = type === 'background'? '0' : '2'

          dropElement.style.left = null
          dropElement.style.top = null
          dropElement.style.zIndex =  type === 'background'? '0' : '2'

          if(type === "background") {
            this.$delete(dragArmorial, _.camelCase(`${dragArmorial.predecessorBG}BG`))
            dragArmorial[_.camelCase(`${successor.name}BG`)] = true

            this.$delete(dropArmorial, _.camelCase(`${dropArmorial.predecessorBG}BG`))
            dropArmorial[_.camelCase(`${dragArmorial.predecessorBG}BG`)] = true

            swapPredecessor(dragArmorial, dropArmorial, 'predecessorBG')
          } else {
            this.$delete(dragArmorial, _.camelCase(`${dragArmorial.predecessorFG}FG`))
            dragArmorial[_.camelCase(`${successor.name}FG`)] = true

            this.$delete(dropArmorial, _.camelCase(`${dropArmorial.predecessorFG}FG`))
            dropArmorial[_.camelCase(`${dragArmorial.predecessorFG}FG`)] = true

            swapPredecessor(dragArmorial, dropArmorial, 'predecessorFG')
          }
        }
        dragElement = null
        dropElement = null
        this.checkResult()
      }
      if(type === 'background') {
        dragImg.src = require('@/assets/img/' + dragArmorial.background + '.png')
        dropImg.src = require('@/assets/img/' + dropArmorial.background + '.png')
      } else {
        dragImg.src = require('@/assets/img/' + dragArmorial.foreground + '.png')
        dropImg.src = require('@/assets/img/' + dropArmorial.foreground + '.png')
      }
    },
    resetDragElement(type) {
      if (this.$dragElement) {
        this.$dragElement.style.zIndex = type === 'background'? '0' : '2'
        this.$dragElement.style.left = null
        this.$dragElement.style.top = null
        this.$set(this.dragItem, 'dragged', false)
      }
      this.$dragElement = null
      this.dragItem = null
    },
    sendMessage(method) {
      if (typeof method === 'string') method = {method: method}
      window.parent.postMessage(method, '*')
      return false
    },
    backgroundClasses (armorial) {
      return {
        'armorial_background': true,
        ...templateBackgroundClasses(armorial)
      }
    },
    foregroundClasses (armorial) {
      return {
        'armorial_foreground': true,
        ...templateForegroundClasses(armorial)
      }
    }
  }
}
</script>
