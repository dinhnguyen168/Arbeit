<template>
  <div id="game-wrapper">
    <div
        v-for="label in labels"
        :key="label.id + 'label'"
        :id="label.id"
        :class="{
          'labels': true,
          draggable: label.draggable,
          dragged: label.dragged,
          placed: label.placed,
          rotated: label.rotated,
          original: label.original,
          plate1 : label.plate1,
          plate2 : label.plate2,
          plate3 : label.plate3
        }"
        @touchstart='startDrag($event, label)'
        @mousedown='startDrag($event, label)'
        @touchend='dragEnd($event, label)'
        @mouseup='dragEnd($event, label)'
    >
      <img :src="require('@/assets/img/' + label.name + '.png')" alt="label.id">
    </div>

    <div
        v-for="plate in plates"
        :key="plate.id + 'plate'"
        :id="plate.id"
        :class="{'plates': true, rotated: plate.rotated}"
    >
      <img :src="require('@/assets/img/' + plate.name + '.png')" alt="label.id">
    </div>
  </div>
</template>

<script>
import {Plate} from "@/utils/symphonion";
import _ from 'lodash'
import {Sound} from "@/utils/sound";

export default {
  name: 'App',
  data() {
    return {
      $dragElement: null,
      dragItem: null,
      labels: [
        {id: 'label-1', name: 'label-alle-meine-entchen', required: 'plate-3', original: true},
        {id: 'label-2', name: 'label-fuchs', original: true},
        {id: 'label-3', name: 'label-haenschen-klein', required: 'plate-1', original: true},
        {id: 'label-4', name: 'label-happy-birthday', required: 'plate-2', original: true},
        {id: 'label-5', name: 'label-jingle-bells', original: true},
      ],
      plates: [],
      placedIds: [],
      draggedIds: [],
      scenario: null,
      counter: 0,
      touchmoveEventListener: (evt) => {
        this.drag(evt)
      },
      touchendEventListener: (evt) => {
        this.dragEnd(evt)
      },
    }
  },
  created() {
    window.FINISHED_TIME = 3 // in Sekunde
    window.CHECK_RESULT_TIME = 1 // in Sekunde
    this.plates = [
      new Plate('plate-1', 'scheibe-1'),
      new Plate('plate-2', 'scheibe-2'),
      new Plate('plate-3', 'scheibe-3'),
    ]
  },
  mounted() {
    this.sound = new Sound()
    this.sound .play('start')
    setTimeout(() => {
      this.sound.play('background')
    }, window.START_TIME * 1000)
  },
  methods: {
    startDrag(evt, label) {
      evt.preventDefault()
      evt.stopPropagation()
      evt.stopPropagation()
      if (!label.rotated && this.draggedIds.length !== 3) {
        this.sound.play('grab')
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
        this.$set(label, 'dragged', true)
        this.dragItem = label
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
        const dropItem = this.plates.filter(plate => plate.checkInside(c, this))[0]
        if (dropItem) {
          this.onDrop(evt, dropItem)
          return
        }
        this.reset()
        this.removeEventListeners()
      }
    },
    removeEventListeners() {
      window.removeEventListener('mousemove', this.touchmoveEventListener)
      window.removeEventListener('touchmove', this.touchmoveEventListener)
      window.removeEventListener('mouseup', this.touchendEventListener)
      window.removeEventListener('touchend', this.touchendEventListener)
    },
    onDrop(evt, dropItem) {
      this.removeEventListeners()
      if (this.dragItem) {
        if (this.draggedIds.length !== 3) {
          this.placedItem(dropItem)
        } else {
          this.resetDragElement()
        }
        this.$dragElement = null
        this.dragItem = null
      }
    },
    // eslint-disable-next-line no-unused-vars
    placedItem(dropItem) {
      let dragItem = this.dragItem
      let dragElement = this.$dragElement
      const img = dragElement.querySelector('img')
      img.onload = () => {
        dragElement.style.left = null
        dragElement.style.top = null
        dragElement.style.zIndex = null
        this.$delete(dragItem, 'dragged')
        this.$delete(dragItem, 'draggable')
        if (dragItem.original && !dropItem.isTakenBy) this.placeOriginalItemIntoEmptyPlate(dropItem, dragItem)
        else if (dragItem.original && dropItem.isTakenBy) {
          if (!dropItem.rotated) this.placeOriginItemIntoTakenPlate(dropItem, dragItem)
          else this.resetDragElement()
        } else if (!dropItem.isTakenBy) this.placePlacedItemIntoEmptyPlate(dropItem, dragItem)
        else this.placePlacedItemIntoTakenPlate(dropItem, dragItem)
        if (this.scenario !== "placePlacedItemIntoEmptyPlate"
            && this.scenario !== "placePlacedItemIntoTakenPlate"
            && !dropItem.rotated) this.draggedIds.push(dragItem.id)
        if (this.scenario !== "placePlacedItemIntoTakenPlate" && !dropItem.rotated) dropItem.isTakenBy = dragItem
        this.scenario = null
        this.checkResult()
      }
      img.src = require('@/assets/img/' + dragItem.name + '.png')
    },
    placeOriginalItemIntoEmptyPlate(dropItem, dragItem) {
      this.sound.play('drop')
      this.scenario = "placeOriginalItemIntoEmptyPlate"
      this.$delete(dragItem, 'original')
      if (dropItem.id === dragItem.required) {
        this.$set(dragItem, 'placed', true)
        this.placedIds.push(dragItem.id)
      } else this.$set(dragItem, _.camelCase(dropItem.id), true)
    },
    placePlacedItemIntoEmptyPlate(dropItem, dragItem) {
      this.sound.play('drop')
      let prePlate = this.plates.find(plate => plate.isTakenBy ? plate.isTakenBy.id === dragItem.id : false)
      prePlate.isTakenBy = null
      this.scenario = "placePlacedItemIntoEmptyPlate"
      if (dropItem.id === dragItem.required) {
        this.$set(dragItem, 'placed', true)
        this.$delete(dragItem, _.camelCase(prePlate.id))
        this.placedIds.push(dragItem.id)
      } else {
        if (prePlate.id === dragItem.required) {
          this.$delete(dragItem, 'placed')
          this.placedIds.splice(this.placedIds.findIndex(item => item === dragItem.id), 1)
        }
        this.$delete(dragItem, _.camelCase(prePlate.id))
        this.$set(dragItem, _.camelCase(dropItem.id), true)
      }
    },
    placeOriginItemIntoTakenPlate(dropItem, dragItem) {
      this.sound.play('drop')
      let placedItem = dropItem.isTakenBy
      this.scenario = "placeOriginItemIntoTakenPlate"
      if (dropItem.id === dragItem.required) {
        this.$delete(placedItem, _.camelCase(dropItem.id))
        this.$set(placedItem, 'original', true)
        this.draggedIds.splice(this.draggedIds.findIndex(item => item === placedItem.id), 1)

        this.$delete(dragItem, 'original')
        this.$set(dragItem, 'placed', true)
        this.placedIds.push(dragItem.id)
      } else {
        if (placedItem.required === dropItem.id) {
          this.$delete(placedItem, 'placed')
          this.placedIds.splice(this.placedIds.findIndex(item => item === placedItem.id), 1)
        } else this.$delete(placedItem, _.camelCase(dropItem.id))
        this.$set(placedItem, 'original', true)
        this.draggedIds.splice(this.draggedIds.findIndex(item => item === placedItem.id), 1)

        this.$delete(dragItem, 'original')
        this.$set(dragItem, _.camelCase(dropItem.id), true)
      }
    },
    placePlacedItemIntoTakenPlate(dropItem, dragItem) {
      this.sound.play('drop')
      let placedItem = dropItem.isTakenBy
      this.scenario = "placePlacedItemIntoTakenPlate"
      if (!dropItem.rotated) {
        let prePlate = this.plates.find(plate => plate.isTakenBy ? plate.isTakenBy.id === dragItem.id : false)
        prePlate.isTakenBy = null
        if (dropItem.id === dragItem.required) {
          if (prePlate.id === dropItem.id && placedItem.id === dragItem.id) {
            dropItem.isTakenBy = dragItem
          } else {
            this.$delete(placedItem, _.camelCase(dropItem.id))
            if (placedItem.required === prePlate.id) {
              this.$set(placedItem, 'placed', true)
              this.placedIds.push(placedItem.id)
            } else this.$set(placedItem, _.camelCase(prePlate.id), true)
            prePlate.isTakenBy = placedItem

            this.$set(dragItem, 'placed', true)
            this.$delete(dragItem, _.camelCase(prePlate.id))
            dropItem.isTakenBy = dragItem
            this.placedIds.push(dragItem.id)
          }
        } else {
          if (prePlate.id === dragItem.required) {
            this.$delete(dragItem, 'placed')
            this.placedIds.splice(this.placedIds.findIndex(item => item === dragItem.id), 1)
          } else this.$delete(dragItem, _.camelCase(prePlate.id))

          this.$set(dragItem, _.camelCase(dropItem.id), true)
          dropItem.isTakenBy = dragItem
          if (dropItem.id === placedItem.required) {
            this.$delete(placedItem, 'placed')
            this.placedIds.splice(this.placedIds.findIndex(item => item === placedItem.id), 1)
          } else this.$delete(placedItem, _.camelCase(dropItem.id))

          if(placedItem.required === prePlate.id) {
            this.$set(placedItem, 'placed', true)
          } else this.$set(placedItem, _.camelCase(prePlate.id), true)
          prePlate.isTakenBy = placedItem
        }
      }
    },
    rotatePlates() {
      if (this.counter !== this.placedIds.length) {
        this.counter = this.placedIds.length
        if (this.counter < 3) this.sound.play('correct')
        else this.sound.play('finished')
      } else {
        this.sound.play('incorrect')
      }

      this.plates
          .filter(plate => plate.isTakenBy)
          .map(plate => {
            this.$set(plate, 'rotated', true)
          })
      this.labels
          .filter(label => this.placedIds.includes(label.id))
          .map(label => {
            this.$set(label, 'rotated', true)
          })
    },
    resetDragElement() {
      this.sound.play('incorrect')
      if (this.$dragElement) {
        this.$dragElement.style.zIndex = 'auto'
        this.$dragElement.style.left = null
        this.$dragElement.style.top = null
        this.$set(this.dragItem, 'dragged', false)
      }
      this.$dragElement = null
      this.dragItem = null
    },
    reset() {
      if (this.$dragElement) {
        this.sound.play('incorrect')
        this.$dragElement.style.zIndex = 'auto'
        this.$dragElement.style.left = null
        this.$dragElement.style.top = null
        this.$set(this.dragItem, 'dragged', false)
        let plateContainDragItem = this.plates.find(plate => plate.isTakenBy ? plate.isTakenBy.id === this.dragItem.id : false)
        if (plateContainDragItem) {
          this.$delete(this.dragItem, _.camelCase(plateContainDragItem.id))
          this.$set(this.dragItem, 'original', true)
          this.draggedIds.splice(this.draggedIds.findIndex(item => item === this.dragItem.id), 1)
          if (this.placedIds.includes(this.dragItem.id)) {
            this.$delete(this.dragItem, 'placed')
            this.placedIds.splice(this.placedIds.findIndex(item => item === this.dragItem.id), 1)
          }
          plateContainDragItem.isTakenBy = null
        }
      }
      this.$dragElement = null
      this.dragItem = null
    },
    checkResult() {
      if (this.draggedIds.length === 3) {
        setTimeout(() => {
          this.reSetupGame()
          this.rotatePlates()
          if (this.placedIds.length === 3) {
            setTimeout(() => {
              this.sendMessage({method: 'showPage', pageID: 340})
            }, window.FINISHED_TIME * 1000)
          }
        }, window.CHECK_RESULT_TIME * 1000)
      }
    },
    reSetupGame() {
      this.draggedIds = [...this.placedIds]
      this.$dragElement = null
      this.dragItem = null
      this.plates.forEach(plate => {
        const platesIDTaken = this.labels
            .filter(label => this.placedIds.includes(label.id))
            .map(label => label.required)
        if (!platesIDTaken.includes(plate.id)) {
          plate.isTakenBy = null
        }
      })
      this.labels
          .filter(label => !this.placedIds.includes(label.id))
          .forEach(label => {
            this.$delete(label, 'dragged')
            this.plates
                .map(plate => {
                  const labelClassName = _.camelCase(plate.id)
                  this.$delete(label, labelClassName)
                })
            this.$set(label, 'original', true)
          })
    },
    sendMessage(method) {
      if (typeof method === 'string') method = {method: method}
      window.parent.postMessage(method, '*')
      return false
    }
  }
}
</script>
