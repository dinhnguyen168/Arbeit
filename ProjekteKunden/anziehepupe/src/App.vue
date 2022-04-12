<template>
  <div id="game-wrapper">
    <div
      id="mannequin"
      @drop='onDrop($event, 1)'
      @dragover.prevent
      @dragenter.prevent
    >
      <img src="../src/assets/img/mannequin.png" alt="body" >
    </div>
    <div
      v-for='item in draggables'
      :key='item.id'
      :id="item.id"
      :class="{draggable: true, dragged: item.dragged, placed: item.placed}"
      @touchstart='startDrag($event, item)'
      @mousedown='startDrag($event, item)'
      @touchend='dragEnd($event, item)'
      @mouseup='dragEnd($event, item)'
     >
      <img :src="require('@/assets/img/' + item.id + '.png')" draggable="false">
      <label><span>{{ item.label }}</span></label>
    </div>
    <div id="message" v-on:click="hideMessage" v-if="message" v-bind:class="{ 'finished': bFinishedMessage }"><span>{{ message }}</span></div>
    <div id="intro-text" v-bind:class="{ 'active': introText }" v-on:click="hideIntro"><img src="../src/assets/img/intro-text.png" alt="body" ></div>
  </div>
</template>

<script>
import Vue from 'vue'

export default {
  name: 'Grassi-Mini-Game',
  data: function () {
    return {
      $mannequin: null,
      dragItem: null,
      $dragElement: null,
      introText: true,
      dropEnabled: true,
      wrongOrderMessages: [
        'Vorher solltest Du etwas anderes anziehen.',
        'Das ist nicht die richtige Reihenfolge.',
        'Vorher fehlt noch etwas anderes.',
        'Das ist nicht ganz richtig.',
        'Überleg noch einmal.',
        'Versuch es erst mit etwas anderem.',
        'Du hast etwas vergessen anzuziehen.'
      ],
      draggables: [
        { id: 'mieder', required: '', label: 'Mieder' },
        { id: 'reifrockgeruest', required: 'mieder', label: 'Panier / Reifrock' },
        { id: 'kleid', required: 'reifrockgeruest', label: 'Kleid' },
        { id: 'schuhe', required: 'kleid', label: 'Schuhe' },
        { id: 'mantel', required: 'kleid', label: 'Mantel', invalid: 'faecher', invalidMsg: 'Der Fächer ist nur für drinnen. Da brauchst Du keinen Mantel.' },
        { id: 'faecher', required: 'kleid', label: 'Fächer', invalid: 'mantel', invalidMsg: 'Den Mantel ziehst Du an, wenn Du nach draußen gehst. Dort brauchst Du keinen Fächer.' }
      ],
      placedIds: [],
      message: null,
      bFinishedMessage: false,
      preloadImageUrls: [],
      touchmoveEventListener: (evt) => { this.drag(evt) },
      touchendEventListener: (evt) => { this.dragEnd(evt) },
      dropEventListener: (evt) => { this.drop(evt) }
    }
  },
  created () {
    // window.addEventListener('touchstart', () => { this.touchstart() })
    // window.addEventListener('touchend', () => { this.touchend() })
    for (const i in this.draggables) {
      Vue.set(this.draggables[i], 'dragged', false)
      this.preloadImageUrls.push(require('@/assets/img/' + this.draggables[i].id + '_placed.png'))
    }
  },
  mounted () {
    this.$mannequin = this.$el.querySelector('#mannequin')
    this.delayPreloadImages()
  },
  methods: {
    delayPreloadImages () {
      const imgs = document.querySelectorAll('div.draggable img')
      for (let i = 0; i < imgs.length; ++i) {
        const img = imgs[i]
        if (!img.complete) {
          // console.log('deplayPreloadImage() waiting for ', img)
          setTimeout(() => { this.delayPreloadImages() }, 1000)
          return
        }
      }
      this.preloadImages()
    },
    preloadImages () {
      if (this.preloadImageUrls.length > 0) {
        const url = this.preloadImageUrls.shift()
        const img = new Image()
        img.onload = () => {
          this.preloadImages()
        }
        // console.log('preload image ', url)
        img.src = url
      }
    },
    hideIntro () {
      this.introText = false
    },
    startDrag (evt, item) {
      evt.preventDefault()
      evt.stopPropagation()
      console.log('startDrag() e:', evt.target, ', item:', item)
      this.hideMessage()
      if (!this.placedIds.includes(item.id)) {
        this.$dragElement = evt.target
        if (this.$dragElement.nodeName === 'IMG') this.$dragElement = this.$dragElement.parentNode
        const box = this.$dragElement.getBoundingClientRect()
        const c = evt.type.match(/^touch/) ? evt.touches[0] : evt
        this.$dragElement.style.zIndex = 99
        this.$dragElement.mouseOffset = { x: c.clientX - box.left, y: c.clientY - box.top }
        Vue.set(item, 'dragged', true)
        this.dragItem = item
        window.addEventListener(evt.type === 'mousedown' ? 'mousemove' : 'touchmove', this.touchmoveEventListener)
        window.addEventListener(evt.type === 'mousedown' ? 'mouseup' : 'touchend', this.touchendEventListener)
      } else {
        if (item.id === 'faecher' || item.id === 'mantel') {
          this.dropEnabled = false
          this.$dragElement = evt.target
          if (this.$dragElement.nodeName === 'IMG') this.$dragElement = this.$dragElement.parentNode
          this.dragItem = item
          console.log(item.id, this.$dragElement)
          // console.log('placedIds:', this.placedIds.length, this.placedIds)
          this.resetPlacedItem()
        }
      }
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
        Vue.set(this.dragItem, 'dragged', false)
      }
    },
    drag (evt) {
      evt.preventDefault()
      evt.stopPropagation()
      if (this.$dragElement && this.$dragElement.mouseOffset) {
        const c = evt.type.match(/^touch/) ? evt.touches[0] : evt
        this.$dragElement.style.left = (c.clientX - this.$dragElement.mouseOffset.x - this.$el.offsetLeft) + 'px'
        this.$dragElement.style.top = (c.clientY - this.$dragElement.mouseOffset.y - this.$el.offsetTop) + 'px'
      }
    },
    dragEnd (evt, item) {
      // console.log('dragEnd() e:', evt)
      if (this.dragItem) {
        const c = evt.type.match(/^touch/) ? evt.changedTouches[0] : evt
        const box = this.$mannequin.getBoundingClientRect()
        const clickMargin = box.width / 4
        // console.log('box:', box, 'clickmargin:', clickMargin, 'mX', c.clientX, 'mY', c.clientY)
        if (box.left + clickMargin < c.clientX && box.right - clickMargin > c.clientX && box.top < c.clientY && box.bottom > c.clientY && this.dropEnabled) {
          this.onDrop(evt)
        } else {
          this.resetDragElement()
          this.dropEnabled = true
        }
        this.$dragElement = null
        this.dragItem = null
        this.removeEventListeners()
      }
    },
    onDrop (evt) {
      // console.log('onDrop() e:', evt)
      this.removeEventListeners()
      if (this.dragItem) {
        this.$dragElement.style.zIndex = 'auto'
        if (this.dragItem.required === '' || this.placedIds.includes(this.dragItem.required)) {
          if (this.dragItem.invalid && this.placedIds.includes(this.dragItem.invalid)) {
            // console.log('Ungültige Kombination')
            this.resetDragElement()
            let msg = this.dragItem.invalidMsg
            const finished = (this.placedIds.length === this.draggables.length - 1)
            if (finished) {
              msg += ' Du bist schon fertig angezogen.'
            }
            this.showMessage(msg, finished)
          } else {
            this.placeItem()
            this.placedIds.push(this.dragItem.id)
            let bOutside = true
            if (this.placedIds.length === 5) {
              for (var n = 0; n < this.placedIds.length; n++) {
                if (this.placedIds[n] === 'faecher') bOutside = false
              }
              // console.log('fertig', (bOutside ? 'draussen' : 'drinnen'))
              var msg = bOutside ? 'Fertig! Mit dem Mantel bist Du perfekt zum Ausgehen angezogen. Draußen brauchst Du keinen Fächer.' : 'Geschafft! Mit dem Fächer hast Du drinnen immer etwas frische Luft. Den Mantel brauchst du da nicht.'
              this.showMessage(msg, true)
            }
            // console.log('placedIds:', this.placedIds.length, this.placedIds)
          }
        } else {
          this.resetDragElement()
          const wrongOrderMessage = this.wrongOrderMessages[Math.floor(Math.random() * this.wrongOrderMessages.length)]
          this.showMessage(wrongOrderMessage, false)
        }
        this.$dragElement = null
        this.dragItem = null
      }
    },
    placeItem () {
      const img = this.$dragElement.querySelector('img')
      var dragItem = this.dragItem
      var dragElement = this.$dragElement
      img.addEventListener('load', () => {
        // console.log('img.load', this)
        const box = this.$mannequin.getBoundingClientRect()
        dragElement.style.width = this.$mannequin.offsetWidth + 'px'
        dragElement.style.height = this.$mannequin.offsetHeight + 'px'
        dragElement.style.left = (box.left - this.$el.offsetLeft) + 'px'
        dragElement.style.top = (box.top - this.$el.offsetTop) + 'px'
        Vue.set(dragItem, 'dragged', false)
        Vue.set(dragItem, 'placed', true)
      })
      img.src = require('@/assets/img/' + this.dragItem.id + '_placed.png')
    },
    resetPlacedItem () {
      const img = this.$dragElement.querySelector('img')
      var dragItem = this.dragItem
      var dragElement = this.$dragElement
      img.addEventListener('load', () => {
        dragElement.style.height = null
        dragElement.style.width = null
        dragElement.style.left = null
        dragElement.style.top = null
        Vue.set(dragItem, 'dragged', true)
        Vue.set(dragItem, 'placed', false)
      })
      img.src = require('@/assets/img/' + this.dragItem.id + '.png')
      for (var n = this.placedIds.length - 1; n >= 0; n--) {
        if (this.placedIds[n] === dragItem.id) {
          this.placedIds.splice(n, 1)
        }
      }
    },
    showMessage (msg, bFinished) {
      this.message = msg
      this.bFinishedMessage = bFinished
      if (this.msgTimeout) {
        clearTimeout(this.msgTimeout)
      }
      this.msgTimeout = setTimeout(() => {
        this.message = null
      }, 5000)
    },
    hideMessage () {
      clearTimeout(this.msgTimeout)
      this.message = null
    },
    sendXpedeoMessage (method) {
      if (typeof method === 'string') method = { method: method }
      window.parent.postMessage(method, '*')
      return false
    },
    touchstart () { return this.sendXpedeoMessage('touchstart') },
    touchend () { return this.sendXpedeoMessage('touchend') },
    showNav () { return this.sendXpedeoMessage('showNav') }

  }
}
</script>
