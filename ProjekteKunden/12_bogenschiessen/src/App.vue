<template>
  <div id="game-wrapper">
    <transition name="fade">
        <div id="intro" v-if="isIntroScreen" @click="hideIntro">
          <img src="./assets/title.png" alt="title"/>
        </div>
    </transition>

    <!--Target-->
    <div class="target"
         :class="target.position"
         v-for="target in shuffledTargets"
         :key="target.index"
    > <div class="shooting-target"/> </div>

    <!--Amor-->
    <div id="amor-shadow">
      <img :src="require('@/assets/amor-shadow.png')" alt=""/>
    </div>
    <div :id="amor.base">
      <img :src="require(`@/assets/${amor.base}.png`)" alt="Amor"/>
    </div>

    <div id="amor-face">
      <img :src="require(`@/assets/amor-face-${stateAmorFace}.jpg`)" alt="Gesicht">
    </div>

    <div v-if="holdingAnArrow"
         :class="{'amor-arm': true, 'place-arrow': amor.arm.arm1.placeArrow, 'get-arrow': amor.arm.arm1.getArrow}"
         :id="amor.arm.arm1.name">
      <img :src="require(`@/assets/${amor.arm.arm1.name}.png`)" :alt="amor.arm.arm1.name">
    </div>

    <div v-else
         :class="{'amor-arm': true, 'place-arrow': amor.arm.arm0.placeArrow}"
         :id="amor.arm.arm0.name">
      <img :src="require(`@/assets/${amor.arm.arm0.name}.png`)" :alt="amor.arm.arm0.name">
    </div>

    <!--Arrow -->
    <div class="arrow"
         :id="arrow.name"
         v-for="arrow in arrowsInQuiver"
         :key="arrow.name"
    >
      <img :src="require(`@/assets/${arrow.name}.png`)" :alt="arrow.name">
    </div>

    <div class="shoot-arrow"
         :id="readyToShootArrow.name"
         v-if="readyToShoot"
         @touchstart='startDrag($event, arrows.find(item => item.id === 5))'
         @mousedown='startDrag($event, arrows.find(item => item.id === 5))'
         @touchend='dragEnd($event)'
         @mouseup='dragEnd($event)'
    > <div id="arrow-head"/> </div>

    <div  class="message" v-if="message" @click="hideMessage()">
      <div class="text-wrapper" v-html="message"/>
    </div>
  </div>
</template>

<script>
export default {
  name: 'App',
  data () {
    return {
      isIntroScreen: true,
      readyToShoot: false,
      holdingAnArrow: true,
      stateAmorFace:0,
      $dragElement: null,
      dragItem : null,
      topOfShootArrow: 0,
      bottomOfShootArrow: 0,
      offsetWithTopOfArrows: 0,
      arrowsClassName : [],
      intervals:[],
      arrowsNumber: 0,
      message: null,
      nStartDragY: 0,
      nStopDragY: 0,
      amor : {
        name: 'amor',
        arm: {
          arm0: {id: 0, name: 'amor-arm-0'},
          arm1: {id: 1, name: 'amor-arm-1'}
        },
        base: 'amor-base',
      },
      arrows: [
        {id: 0, name: 'arrow-0'}, {id: 1, name: 'arrow-1'}, {id: 2, name: 'arrow-2'},
        {id: 3, name: 'arrow-3'}, {id: 4, name: 'arrow-4'}, {id: 5, name: 'arrow'}
      ],
      targets: [
        {id: 0, name: 'target-0'}, {id: 1, name: 'target-1'}, {id: 2, name: 'target-2'},
        {id: 3, name: 'target-3'}, {id: 4, name: 'target-4'}, {id: 5, name: 'target-5'},
      ],
      dummyTargets: [],
      touchmoveEventListener: (evt) => { this.drag(evt) },
      touchendEventListener: () => { this.dragEnd() },
    }
  },
  computed: {
    arrowsInQuiver(){
      return this.arrows.filter(arrow => arrow.id !== 5)
    },
    readyToShootArrow() {
      return this.arrows.find(arrow => arrow.id === 5)
    },
    shuffledTargets(){
      let dummyTargets = [...this.targets].sort(() => Math.random() - 0.5)
      for (let i = 0; i < dummyTargets.length; i++) {
        dummyTargets[i].position = `target-position-${i}`
      }
      return dummyTargets
    },
  },
  methods: {
    hideIntro(){
      this.isIntroScreen = false
      this.targetsMove()
      this.placeArrowToShoot()
    },
    hideMessage(){
      this.message = null
      this.resetGame()
    },
    showMessage(stateAmorFace = 0) {
      this.intervals.forEach(interval => clearInterval(interval))
      if (stateAmorFace !== 6) this.message = '<p>Das war nicht schlecht.<br> Willst du es noch einmal probieren?</p>'
      else this.message = '<p>Bravo!<br> Du hast mit jedem Pfeil getroffen.<br> Versuche es doch gleich nochmal!</p>'
    },
    startDrag(evt) {
      evt.preventDefault()
      evt.stopPropagation()
      this.$dragElement = evt.target
      if (this.$dragElement.nodeName === 'IMG') this.$dragElement = this.$dragElement.parentNode
      const box = this.$dragElement.getBoundingClientRect()
      this.topOfShootArrow = box.top
      this.bottomOfShootArrow = box.bottom
      const c = evt.type.match(/^touch/) ? evt.touches[0] : evt
      this.$dragElement.mouseOffset = { x: c.clientX - box.left, y: c.clientY - box.top }
      window.addEventListener(evt.type === 'mousedown' ? 'mousemove' : 'touchmove', this.touchmoveEventListener)
      window.addEventListener(evt.type === 'mousedown' ? 'mouseup' : 'touchend', this.touchendEventListener)
      this.nStartDragY = c.clientY
    },
    drag(evt) {
      evt.stopPropagation()
      if (this.$dragElement && this.$dragElement.mouseOffset) {
        const c = evt.type.match(/^touch/) ? evt.touches[0] : evt

        this.nStopDragY = c.clientY
        this.offsetWithTopOfArrows = (c.clientY - this.$dragElement.mouseOffset.y) / 100

        if(c.clientY - this.$dragElement.mouseOffset.y > this.topOfShootArrow
            && c.clientY - this.$dragElement.mouseOffset.y < this.bottomOfShootArrow - (this.topOfShootArrow / 3)) {
          this.$dragElement.style.top = (c.clientY - this.$dragElement.mouseOffset.y) + 'px'
        }
      }
    },
    async dragEnd () {
      try{
        let arrowVelocity = null

        this.removeEventListeners()
        let arrowMove = setInterval(() => {
          let arrTop = parseInt(window.getComputedStyle(this.$dragElement).getPropertyValue('top'))
          let arrHeight = parseInt(window.getComputedStyle(this.$dragElement).getPropertyValue('height'))

          // die folgende berechnung der pfeilgeschwidigkeit ist etwas besser als vorher. aber jetzt wird der pfeil auf kleinen displays etwas schneller als auf grossen
          let arrowSpeed = Math.max(4, 0.2 * Math.max(1, Math.min(100, ((((this.nStopDragY - this.nStartDragY) * 100) / window.innerWidth) * 5))))
          arrowVelocity = arrowSpeed

          // /* alte berechnung funktioniert nicht: bei grossen displays wird der pfeil viel schneller als bei kleinen */
          // if(this.offsetWithTopOfArrows < 5.5) arrowVelocity = this.offsetWithTopOfArrows
          // else if (this.offsetWithTopOfArrows < 6.5) arrowVelocity = this.offsetWithTopOfArrows * 1.5
          // else arrowVelocity = this.offsetWithTopOfArrows * 3.5

          this.$dragElement.style.top = (arrTop - arrowVelocity)+ 'px'
          this.$dragElement.style.zIndex = '99'
          this.collisionDetection()
          if(arrTop < arrHeight * (-1)){
            clearInterval(arrowMove)
            if(this.arrows.length === 1){
              this.showMessage(this.stateAmorFace)
            } else {
              this.readyToShoot = false
              this.getAndPlaceArrowToShoot()
            }
          }
        }, 10)
      } catch (e) {
        console.log(e.message)
      }
    },
    removeEventListeners () {
      window.removeEventListener('mousemove', this.touchmoveEventListener)
      window.removeEventListener('touchmove', this.touchmoveEventListener)
      window.removeEventListener('mouseup', this.touchendEventListener)
      window.removeEventListener('touchend', this.touchendEventListener)
    },
    collisionDetection() {
      let shootingTargets = this.$el.getElementsByClassName('shooting-target')
      let arrowShoot = this.$el.querySelector('#arrow-head')

      for(let i = 0; i < shootingTargets.length; i++){
        let shootTg = shootingTargets[i]
        let targetBound = shootTg.getBoundingClientRect()
        let arrowBound = arrowShoot.getBoundingClientRect()

        if(arrowBound.left >= targetBound.left
            && arrowBound.right <= targetBound.right
            && arrowBound.top >= targetBound.top
            && arrowBound.bottom <= targetBound.bottom){
          shootTg.parentElement.parentElement.removeChild(shootTg.parentElement)
          this.stateAmorFace += 1
        }
      }
    },
    // Die Ziele auf dem Band läuft unendlich von rechts nach links
    async targetsMove(){
      try {
        let targetMove = setInterval(() => {
          let targets = this.$el.getElementsByClassName('target')
          this.placeNewTargets()
          for (let i = 0; i < targets.length; i++) {
            let tg = targets[i]
            let tgLeft = parseInt(window.getComputedStyle(tg).getPropertyValue('left'))
            let tgWidth = parseInt(window.getComputedStyle(tg).getPropertyValue('width'))
            let tempo = 2
            tg.style.left = (tgLeft - tempo) + 'px'
            if (tgLeft + tgWidth < 0) {
              tg.parentElement.removeChild(tg)
            }
          }
        }, 20)
        this.intervals.push(targetMove)
      } catch (e) {
        console.log(e.message)
      }
    },
    //Setzen neu Ziele direck hinter das letzte Ziel in Zieleband
    placeNewTargets() {
      let targets = this.$el.getElementsByClassName('target')
      if(targets.length < 8) {
        let wrapper = document.querySelector('#game-wrapper')
        let lastTargetLeft = parseInt(window.getComputedStyle(targets[targets.length - 1]).getPropertyValue('left'))
        let lastTargetWidth = parseInt(window.getComputedStyle(targets[targets.length - 1]).getPropertyValue('width'))
        let target = document.createElement('div')
        this.generateTarget(target, lastTargetLeft, lastTargetWidth)
        wrapper.appendChild(target)
      }
    },
    //Generieren neue Ziele sodass es keine Duplikate gleichzeitg gibt.
    generateTarget(target, lastTargetLeft,lastTargetWidth){
      if(this.arrowsClassName.length === 0) this.arrowsClassName = ['target-position-0', 'target-position-1', 'target-position-2', 'target-position-3', 'target-position-4', 'target-position-5']
      let removeElem = this.arrowsClassName.sort(() => Math.random() - 0.5).shift()
      let shootingTarget = document.createElement('div')
      shootingTarget.classList.add('shooting-target')
      target.appendChild(shootingTarget)
      target.classList.add('target')
      target.classList.add(removeElem)
      target.style.left = lastTargetLeft + lastTargetWidth * (17.5/26) + 'px'
    },
    async placeArrowToShoot(){
      try {
        setTimeout(() => {
          this.$set(this.amor.arm.arm1, 'placeArrow', true)
        }, 500)
        setTimeout(() => {
          this.$set(this.amor.arm.arm1, 'placeArrow', true)
        }, 700)
        setTimeout(() => {
          this.holdingAnArrow = false
          this.$set(this.amor.arm.arm0, 'placeArrow', true)
          this.readyToShoot = true
        }, 850)
        setTimeout(() => {
          this.$set(this.amor.arm.arm0, 'placeArrow', false)
        }, 1000)
      } catch (e){
        console.log(e.message)
      }
    },
    async getAndPlaceArrowToShoot(){
      try {
        setTimeout(()=>{
          this.holdingAnArrow = true
          this.arrows.shift()
          this.$set(this.amor.arm.arm1, 'getArrow', true)
        }, 500)
        setTimeout(()=>{
          this.$set(this.amor.arm.arm1, 'getArrow', false)
        }, 700)
        setTimeout(()=>{
          this.$set(this.amor.arm.arm1, 'placeArrow', true)
        }, 900)
        setTimeout(()=>{
          this.holdingAnArrow = false;
          this.$set(this.amor.arm.arm0, 'placeArrow', true)
          this.readyToShoot = true
        }, 1100)
        setTimeout(()=>{
          this.$set(this.amor.arm.arm0, 'placeArrow', false)
        }, 1300)
      } catch (e){
        console.log(e.message)
      }
    },
    resetGame() {
      this.isIntroScreen = true
      this.readyToShoot = false
      this.stateAmorFace = 0
      this.holdingAnArrow = true
      this.$dragElement = null
      this.dragItem  = null
      this.topOfShootArrow = 0
      this.bottomOfShootArrow = 0
      this.offsetWithTopOfArrows = 0
      this.arrowsClassName  = []
      this.intervals =[]
      this.message = null
      this.arrows = [
        {id: 0, name: 'arrow-0'}, {id: 1, name: 'arrow-1'}, {id: 2, name: 'arrow-2'},
        {id: 3, name: 'arrow-3'}, {id: 4, name: 'arrow-4'}, {id: 5, name: 'arrow'}
      ]
      this.$delete(this.amor.arm.arm1, 'placeArrow')
      this.$delete(this.amor.arm.arm1, 'getArrow')
      this.$delete(this.amor.arm.arm0, 'placeArrow')
    },
  },
}
</script>
