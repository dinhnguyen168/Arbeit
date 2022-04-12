<template>
  <div id="game-wrapper">
    <div
        v-for="key in initKeys"
        :key="key.id"
        :id="key.id"
        :class="{'key-img': true, draggable: key.draggable, pos1: key.pos1, pos2: key.pos2}"
        @touchstart='startDrag($event, key)'
        @mousedown='startDrag($event, key)'
        @touchend='dragEnd($event, key)'
        @mouseup='dragEnd($event, key)'
    >
      <img :src="require('@/assets/img/' + key.name + '.png')" alt="key.id">
    </div>
<!--    <div class="message" v-if="done_message" v-on:click="resetGame">-->
<!--      <div class="popup">-->
<!--        <p v-html="showMessage('done')"></p>-->
<!--      </div>-->
<!--    </div>-->
  </div>
</template>

<script>
import { Sound } from '@/utils/sound'

export default {
  name: 'App',
  data() {
    return {
      mission_done: false,
      $dragElement: null,
      dragItem: null,
      keys: [
        {id: 'key-1',  name: 'key_1', display: false},
        {id: 'key-11', name: 'key_1.1', required: 'key-12', result: 'key-1', draggable: true, display: true},
        {id: 'key-12', name: 'key_1.2', required: 'key-11', result: 'key-1', draggable: true, display: true},
        {id: 'key-13', name: 'key_4', draggable: true, display: true},
        {id: 'key-2',  name: 'key_2', display: false},
        {id: 'key-21', name: 'key_2.1', required: 'key-22', result: 'key-2', draggable: true, display: true},
        {id: 'key-22', name: 'key_2.2', required: 'key-21', result: 'key-2', draggable: true, display: true},
        {id: 'key-23', name: 'key_5', draggable: true, display: true},
        {id: 'key-3',  name: 'key_3', display: false},
        {id: 'key-31', name: 'key_3.1', required: 'key-32', result: 'key-3', draggable: true, display: true},
        {id: 'key-32', name: 'key_3.2', required: 'key-31', result: 'key-3', draggable: true, display: true},
        {id: 'key-33', name: 'key_6', draggable: true, display: true},
      ],
      done_message: false,
      messages: [
        {id: '0', name: 'done', html: '<b>Sehr gut,</b><br> du hast alle Schlüssel gefunden.'},
      ],
      placedIds: [],
      sound: null,
      touchmoveEventListener: (evt) => { this.drag(evt) },
      touchendEventListener: (evt) => { this.dragEnd(evt) },
    }
  },
  created() {
    window.START_TIME = 1.5 // in Sekunden
    window.FINISHED_TIME = 2.5 // in Sekunde
  },
  mounted() {
    this.sound = new Sound()
    this.sound.play('start')
    setTimeout(() => {
      this.sound.play('background')
    }, window.START_TIME * 1000)
  },
  computed: {
    initKeys() {
      return this.keys.filter(key => key.display)
    }
  },
  methods: {
    startDrag(evt, key) {
      evt.preventDefault()
      evt.stopPropagation()
      if (!this.placedIds.includes(key.id) && this.placedIds.length !== 3) {
        this.sound.play("grab")
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
        this.$set(key, 'dragged', true)
        this.dragItem = key
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
    dragEnd(evt) {
      if (this.dragItem) {
        const c = evt.type.match(/^touch/) ? evt.changedTouches[0] : evt
        let box = this.dragItem.required ? this.$el.querySelector('#' + this.dragItem.required).getBoundingClientRect() : null
        if (box) {
          const clickMargin = box.width / 4
          if (box.left < c.clientX && box.right - clickMargin > c.clientX && box.top < c.clientY && box.bottom > c.clientY) {
            this.onDrop(evt)
            if (this.placedIds.length === 3){
              this.sound.play('finished')
              setTimeout(()=> {
                this.sendMessage({method: 'showPage', pageID: 340})
                this.sound.stop()
              }, window.FINISHED_TIME * 1000)
            } else {
              this.sound.play('correct')
            }
            return
          }
        }
        this.resetDragElement()
        this.$dragElement = null
        this.dragItem = null
        this.removeEventListeners()
      }
    },
    removeEventListeners() {
      window.removeEventListener('mousemove', this.touchmoveEventListener)
      window.removeEventListener('touchmove', this.touchmoveEventListener)
      window.removeEventListener('mouseup', this.touchendEventListener)
      window.removeEventListener('touchend', this.touchendEventListener)
    },
    onDrop() {
      this.removeEventListeners()
      if (this.dragItem) {
        if(this.placedIds.length !== 3) {
          this.placeItem()
          this.placedIds.push(this.dragItem.result)
        } else {
          this.resetDragElement()
        }
        this.$dragElement = null
        this.dragItem = null
      }
    },
    placeItem() {
      let dragItem = this.dragItem
      this.keys.map(key => {
        if(key.id === dragItem.id || key.id === dragItem.required) {
          key.display = false
        }
      })
      const foundKey = this.keys.find(key => key.id === dragItem.result)
      this.$set(foundKey, `pos${dragItem.required.slice(-1)}`, true)
      this.$set(foundKey, 'display', true)
    },
    resetDragElement() {
      if (this.$dragElement) {
        this.sound.play('incorrect')
        this.$dragElement.style.zIndex = 'auto'
        this.$dragElement.style.left = null
        this.$dragElement.style.top = null
        this.$set(this.dragItem, 'dragged', false)
      }
    },
    showMessage(mission_done) {
      return this.messages.find(msg => msg.name === mission_done).html
    },
    resetGame() {
      this.sendMessage({method: 'showPage', pageID: 340})
      this.done_message = false
      this.placedIds = []
      this.$dragElement = null
      this.dragItem = null
      this.keys.forEach(item => {
        if(!item.draggable) item.display = false
        else item.display = true
        this.$delete(item, 'dragged')
        this.$delete(item, 'pos1')
        this.$delete(item, 'pos2')
      })
    },
    sendMessage (method) {
      if (typeof method === 'string') method = { method: method }
      window.parent.postMessage(method, '*')
      return false
    }
  }
}
</script>
