<template>
  <div id="game-wrapper" @click="stepForward">
    <div
        v-for='item in draggableElements'
        :key='item.id'
        :id="item.id"
        :class="{ 'active': showDraggables, draggable: true, dragged: item.dragged, placed: item.placed}"
        @touchstart='startDrag($event, item)'
        @mousedown='startDrag($event, item)'
        @touchend='dragEnd($event, item)'
        @mouseup='dragEnd($event, item)'
    >
      <img :src="require('@/assets/img/'+ item.id + '.png')" alt="item.id">
    </div>

    <div
        v-for="item in dropableElements"
        :key="item.id"
        :id="item.id"
        :class="{'active': !introText, 'gargoyle': true}"
        @drop='onDrop($event, 1)'
        @dragover.prevent
        @dragenter.prevent
    >
      <img :src="require('@/assets/img/' + item.id + '.png')" alt="item.id">
    </div>

    <div id="intro-text" :class="{ 'active': introText }" @click="hideIntro">
      <h1>Was sind Sünden? </h1>

      <p><b>Laster</b>, also schlechte Angewohnheiten. </p>

      <p>Die <b>7 Hauptsünden</b> sind Sünden, aus <br>
        denen andere Sünden entstehen können.</p>

      <p><b>Kannst Du 5 der Sünden <br>
        den richtigen Gestalten zuordnen?</b></p>
    </div>
    <div id="titleBar" :class="{'active': title}" @click="hideIntro"><img src="@/assets/img/title.jpg" alt="title"></div>
    <div id="barWithoutTitle" ><img src="@/assets/img/balken.jpg" alt="title"></div>
    <div class="message" v-on:click="hideMessage" v-if="selectSins">
      <div :class="{'popup':true, 'small': selectSins==='wollust'}">
        <p v-html="showMessage(selectSins)"></p>
      </div>
    </div>
    <div class="message" v-if="done_message" v-on:click="resetGame">
      <div class="popup">
        <p v-html="showMessage('done')"></p>
      </div>
    </div>
  </div>
</template>

<script>

export default {
  name: 'Home',
  data() {
    return {
      $dragElement: null,
      dragItem: null,
      introText: false,
      title: true,
      showDraggables: false,
      done_message: false,
      selectSins: null,
      draggableElements: [
        {id: 'zorn', required: 'gargoyle_zorn'},
        {id: 'geiz', required: 'gargoyle_geiz'},
        {id: 'voellerei', required: 'gargoyle_voellerei'},
        {id: 'hochmut', required: 'gargoyle_hochmut'},
        {id: 'wollust', required: 'gargoyle_wollust'},
        {id: 'traegheit'},
        {id: 'neid'},
      ],
      dropableElements: [
        {id: 'gargoyle_zorn'},
        {id: 'gargoyle_geiz'},
        {id: 'gargoyle_voellerei'},
        {id: 'gargoyle_hochmut'},
        {id: 'gargoyle_wollust'},
      ],
      messages: [
        {id: '0', name:'done', html: '<b>Sehr gut,</b><br> du hast die Laster der<br> 5 Gestalten erkannt.<br>Neid und Trägheit sind leider verloren gegangen.'},
        {id: '1', name:'geiz', html: 'Ein geiziger Mensch hat immer Angst um seinen Geldtopf! '},
        {id: '2', name:'hochmut', html: 'Pass auf du überheblicher Ritter, dass du vor lauter Stolz und Übermut nicht vom Pferd fällst.'},
        {id: '3', name:'voellerei', html: 'Schweinchen, friss nicht so viel! <br>Sonst platzt du gleich.'},
        {id: '4', name:'wollust', html: 'Zu viel Lust oder <br>Genuss tut nicht gut! '},
        {id: '5', name:'zorn', html: 'Achtung – Wer wütend tobt und zornig brüllt, wird fast zum Tier! '},
      ],
      placedIds: [],
      touchmoveEventListener: (evt) => { this.drag(evt) },
      touchendEventListener: (evt) => { this.dragEnd(evt) },
    }
  },
  methods : {
    stepForward () {
      if (this.title) {
        this.title = false
        this.introText = true
      } else {
        this.introText = false
        this.showDraggables = true
      }
    },
    hideIntro(){
      // this.introText = false
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
        const c = evt.type.match(/^touch/) ? evt.touches[0] : evt
        this.$dragElement.style.left = (c.clientX - this.$dragElement.mouseOffset.x - this.$el.offsetLeft) + 'px'
        this.$dragElement.style.top = (c.clientY - this.$dragElement.mouseOffset.y - this.$el.offsetTop) + 'px'
      }
    },
    dragEnd (evt) {
      if (this.dragItem) {
        const c = evt.type.match(/^touch/) ? evt.changedTouches[0] : evt
        let box = this.dragItem.required ? this.$el.querySelector('#' + this.dragItem.required).getBoundingClientRect() : null
        if(box) {
          const clickMargin = box.width / 4
          if (box.left < c.clientX && box.right - clickMargin > c.clientX && box.top < c.clientY && box.bottom > c.clientY  ) {
            this.onDrop(evt)
            return
          }
        }
        this.resetDragElement()
        this.$dragElement = null
        this.dragItem = null
        this.removeEventListeners()
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
        this.$set(this.dragItem, 'dragged', false)
      }
    },
    onDrop () {
      this.removeEventListeners()
      if (this.dragItem) {
        this.$dragElement.style.zIndex = 'auto'
        if (this.placedIds.length !== 5) {
          this.placeItem()
          this.placedIds.push(this.dragItem.id)
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
      this.$el.querySelector('#' + dragItem.required).className += ' hidden-leg'
      this.selectSins = dragItem.id
      img.onload = () => {
        dragElement.style.left = null
        dragElement.style.top = 'initial'
        dragElement.style.zIndex = '1'
        this.$set(dragItem, 'dragged', false)
        this.$set(dragItem, 'placed', true)
      }
      img.src = require('@/assets/img/' + this.dragItem.id + '.png')
    },
    showMessage (selectSins) {
      return this.messages.find(msg=> msg.name === selectSins).html
    },
    hideMessage () {
      this.selectSins = null
      this.done_message = this.placedIds.length >= 5
    },
    resetGame () {
      this.introText = true
      this.done_message = false
      this.placedIds = []
      this.$dragElement = null
      this.dragItem = null
      this.draggableElements.forEach( item => {
        let dragElement = this.$el.querySelector('#' + item.id)
        dragElement.style.zIndex = null
        dragElement.style.left = null
        dragElement.style.top = null
        this.$delete(item, 'dragged')
        this.$delete(item, 'placed')
      })
    }
  }
}
</script>
