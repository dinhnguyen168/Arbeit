<template>
  <div id="game-wrapper">
    <div id="start-screen"
         @click="hideStartScreen"
         v-if="isStartScreen"
    >
      <div class="jacobus">
        <img :src="require('@/assets/jacobus_03.png')" alt="jacobus" draggable="false">
      </div>
      <div id="title">
        <img :src="require('@/assets/Titel.png')" alt="" draggable="false">
      </div>
    </div>

    <div id="main-screen" v-else>
      <div class="jacobus"
           @drop='onDrop($event, 1)'
           @dragover.prevent
           @dragenter.prevent>
        <img :src="require('@/assets/'+ chooseJacobus(jacobus_nth) + '.png')" alt="" draggable="false">
      </div>

      <div :id="item.id"
           :class="{draggable: true, dragged: item.dragged, wore: item.wore, placed: item.placed}"
           v-for='item in draggables'
           :key='item.id'
           @touchstart='startDrag($event, item)'
           @mousedown='startDrag($event, item)'
           @touchend='dragEnd($event, item)'
           @mouseup='dragEnd($event, item)'>
        <img :src="require('@/assets/' + item.label + '.png')" draggable="false">
      </div>

      <div :id="stateMessage" class="message" v-if="message" @click="hideMessage">
        <div class="text-wrapper" v-html="message"/>
      </div>
    </div>
  </div>
</template>
<script>

export default {
  name: 'Spiel_Jacobus_Margarete',
  data() {
    return {
      isStartScreen: true,
      introText: false,
      endMess: false,
      dragItem: null,
      $dragElement: null,
      jacobus_nth: 1,
      draggables: [
        { id: 'muschel', label: 'Muschel', required: 'stab' , jacobus_id: 7, message: '<p>Dies ist das Zeichen des Jakobswegs. Alle Pilger tragen es mit sich.</p>'},
        { id: 'stöckelschuhe', label: 'Stöckelschuhe', message: '<p>Mit diesen Schuhen wirst du nicht weit kommen. Es gibt bequemere!</p>'},
        { id: 'roller', label: 'Roller', message: '<p>Es geht nicht um Geschwindigkeit beim Pilgern.</p>'},
        { id: 'stab', label: 'Stab', required: 'wasserflasche', jacobus_id: 6, message: '<p>Mit dem Stab geht das Pilgern leichter.</p>'},
        { id: 'fliege', label: 'Fliege', message: '<p>Fein anziehen brauchst du dich beim Pilgern nicht.</p>'},
        { id: 'rollschuhe', label: 'Rollschuhe', message: '<p>Gute Idee! Aber es geht auch oft über steinige Wege. Lass die Rollschuhe besser zu Hause.</p>'},
        { id: 'wasserflasche', label: 'Wasserflasche', required: 'rucksack', jacobus_id: 5, message: '<p>Unterwegs müssen wir viel trinken.</p>'},
        { id: 'rucksack', label: 'Rucksack', required: 'wanderschuhe', jacobus_id: 4, message: '<p>Mit dem Rucksack können wir gut unser Gepäck tragen. Aber pack ihn nicht zu voll.</p>'},
        { id: 'wanderschuhe', label: 'Wanderschuhe', required: 'jacke', jacobus_id: 3, message: '<p>Die sind bequem bei langen Wanderungen! Und du spürst nicht jeden Stein unter den Füßen.</p>'},
        { id: 'trolley', label: 'Trolley', message: '<p>Den ganzen Weg einen Koffer ziehen? Das wird zu anstrengend und so viel Gepäck brauchen wir nicht.</p>'},
        { id: 'jacke', label: 'Jacke', required: 'nothing', jacobus_id: 2, message: '<p>Die ist bequem und bei jedem Wetter geeignet.</p>'},
      ],
      placedIds: [],
      jacobus_ids: [],
      startMessage: '<p>Lass uns zusammen auf den Jakobsweg gehen. <br>Suchst du alles zusammen, was wir dafür brauchen?</p>',
      endMessage: '<h2>Sehr gut!</h2> <p>Jetzt haben wir alles, was wir brauchen. Dann lass uns loslaufen.</p>',
      message: null,
      stateMessage: null,
      startAnimation: false,
      touchmoveEventListener: (evt) => { this.drag(evt) },
      touchendEventListener: (evt) => { this.dragEnd(evt) },
    }
  },
  methods: {
    hideStartScreen() {
      this.isStartScreen = false
      this.introText = true
      this.showMessage(this.startMessage, 'intro-text')
    },
    hideMessage() {
      if(this.jacobus_nth === 7 && this.message === this.draggables.find(item => item.id === 'muschel').message) this.endMess = true
      this.message = null
      let requiredItem = this.jacobus_ids
          .filter(item => item !== 'go')
          .filter(item => item === this.jacobus_nth + 1).length
      if(this.jacobus_ids.length > 0 && requiredItem) this.startAnimation = true
    },
    chooseJacobus(id) {
      if(id < 10) {
        return `jacobus_0${id.toString()}`
      }
      return `jacobus_${id.toString()}`
    },
    showMessage(msg, stateMess='main-message') {
      this.message = msg
      this.stateMessage = stateMess
    },
    startDrag(evt, item) {
      evt.preventDefault()
      evt.stopPropagation()
      if (!this.placedIds.includes(item.id)) {
        this.$dragElement = evt.target
        if (this.$dragElement.nodeName === 'IMG') this.$dragElement = this.$dragElement.parentNode
        const box = this.$dragElement.getBoundingClientRect()
        const c = evt.type.match(/^touch/) ? evt.touches[0] : evt
        this.$dragElement.mouseOffset = { x: c.clientX - box.left, y: c.clientY - box.top }
        this.$set(item, 'dragged', true)
        this.dragItem = item
        window.addEventListener(evt.type === 'mousedown' ? 'mousemove' : 'touchmove', this.touchmoveEventListener)
        window.addEventListener(evt.type === 'mousedown' ? 'mouseup' : 'touchend', this.touchendEventListener)
      }
    },
    drag (evt) {
      evt.preventDefault()
      evt.stopPropagation()
      if (this.$dragElement && this.$dragElement.mouseOffset) {
        this.$dragElement.style.zIndex = "99"
        const c = evt.type.match(/^touch/) ? evt.touches[0] : evt
        this.$dragElement.style.left = (c.clientX - this.$dragElement.mouseOffset.x - this.$el.offsetLeft) + 'px'
        this.$dragElement.style.top = (c.clientY - this.$dragElement.mouseOffset.y - this.$el.offsetTop) + 'px'
      }
    },
    dragEnd (evt) {
      if (this.dragItem) {
        const c = evt.type.match(/^touch/) ? evt.changedTouches[0] : evt
        let box = this.$el.querySelector('.jacobus').getBoundingClientRect()
        if(box) {
          const clickMargin = box.width / 4
          if (box.left + clickMargin < c.clientX && box.right - clickMargin > c.clientX && box.top < c.clientY && box.bottom > c.clientY) {
            this.onDrop(evt)
            return
          }
          this.resetDragElement()
        }
        this.removeEventListeners()
      }
    },
    onDrop () {
      this.removeEventListeners()
      this.showMessage(this.dragItem.message)
      if (this.dragItem) {
        this.$dragElement.style.zIndex = 'auto'
        if (this.dragItem.required) {
          this.placedIds.push(this.dragItem.id)
          this.jacobus_ids.push(this.dragItem.jacobus_id)
          this.placeItem()
        } else {
          this.resetDragElement()
        }
        this.$dragElement = null
        this.dragItem = null
      }
    },
    placeItem () {
      const img = this.$dragElement.querySelector('img')
      let dragItem = this.dragItem
      let dragElement = this.$dragElement
      img.onload = () => {
        dragElement.style.left = null
        dragElement.style.top = null
        dragElement.style.zIndex = null
        this.$set(dragItem, 'dragged', false)
        this.$set(dragItem, 'placed', true)
      }
      img.src = require('@/assets/' + this.dragItem.label + '.png')
      if (dragItem.jacobus_id === this.jacobus_nth + 1) {
        this.$set(dragItem, 'wore', true)
        this.jacobus_nth = dragItem.jacobus_id
        this.jacobus_ids = this.jacobus_ids
            .filter(item => item !== 'go')
            .filter(item => item !== this.jacobus_nth)
      }
    },
    resetGame () {
      this.isStartScreen = true
      this.dragItem = null
      this.$dragElement = null
      this.message = null
      this.endMess = false
      this.stateMessage = null
      this.startAnimation = false
      this.jacobus_nth = 1
      this.placedIds = []
      this.jacobus_ids = []
      this.draggables.forEach( item => {
        let dragElement = this.$el.querySelector('#' + item.id)
        dragElement.style.zIndex = null
        dragElement.style.left = null
        dragElement.style.top = null
        this.$delete(item, 'dragged')
        this.$delete(item, 'wore')
        this.$delete(item, 'placed')
      })
    },
    removeEventListeners () {
      window.removeEventListener('mousemove', this.touchmoveEventListener)
      window.removeEventListener('touchmove', this.touchmoveEventListener)
      window.removeEventListener('mouseup', this.touchendEventListener)
      window.removeEventListener('touchend', this.touchendEventListener)
    },
    resetDragElement () {
      if (this.$dragElement) {
        this.$dragElement.style.zIndex = 'auto'
        this.$dragElement.style.left = null
        this.$dragElement.style.top = null
        this.$set(this.dragItem, 'dragged', false)
      }
    }
  },
  watch : {
    async jacobus_ids(val) {
      if(val.filter(item => item !== 'go').length > 0 && this.startAnimation) {
        setTimeout(() => {
          let temp = val.sort((x, y) => x - y).shift()
          this.jacobus_nth = temp === 'go' || temp === undefined ? this.jacobus_nth : temp
          let dragItemId = this.draggables.find(item => item.jacobus_id === this.jacobus_nth)
          this.$set(dragItemId, 'wore', true)
          let requiredItem = this.jacobus_ids
              .filter(item => item !== 'go' && item === this.jacobus_nth + 1).length
          if (!requiredItem) this.startAnimation = false
        }, 500)
      }
      if(this.jacobus_nth === 7 && !this.message) {
        setTimeout(() => {
          this.showMessage(this.endMessage, 'end-message')
        }, 1000)
      }
    },
    startAnimation(val) {
      if(val) this.jacobus_ids.push('go')
    },
    async message(){
      if(this.jacobus_nth === 7 && !this.message && this.endMess) {
        setTimeout(() => {
          this.showMessage(this.endMessage, 'end-message')
          this.endMess = false
        }, 1000)
      }
    }
  }
}
</script>
