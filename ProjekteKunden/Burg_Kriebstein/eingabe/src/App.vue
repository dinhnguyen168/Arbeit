<template>
  <div id="game-wrapper">
    <div id="lock-plate">
      <div id="lock-wrapper">
        <div class="welcome-message">Geschafft!</div>
        <div class="lock-dial" v-for="dial in dials" :key="'dial' + dial" :id="'dial-' + dial">
          <ul
              data-combo-num="0"
              @mousedown="startDrag($event)"
              @touchstart="startDrag($event)"
              @touchend="dragEnd($event)"
              @mouseup="dragEnd($event)"
          >
            <li v-for="number in numbers" :key="'dial-' + dial + '-' + number">{{number}}</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
// import { Sound } from '@/utils/sound'

export default {
  name: 'App',
  data() {
    return {
      $dragElement: null,
      dials: ['one', 'two', 'three', 'four'],
      numbers: [5,6,7,8,9,0,1,2,3,4],
      gridIncrement: 0,
      halfHeight : 0,
      initTop: 0,
      handleMouseMoveEvent: (evt) => { this.drag(evt) },
      handleMouseUpEvent: (evt) => { this.dragEnd(evt) },
    }
  },
  created() {
    window.START_TIME = 1.5 // in Sekunden
    window.FINISHED_TIME = 2.5 // in Sekunde
  },
  mounted() {
    // this.sound = new Sound()
    // this.sound.play('start')
    // setTimeout(() => {
    //   this.sound.play('background')
    // }, window.START_TIME * 1000)

    this.gridIncrement = window.getComputedStyle(this.$el.getElementsByTagName('ul')[0])
        .getPropertyValue('line-height')
        .replace('px', '') / 2
    this.halfHeight = this.gridIncrement * this.numbers.length
    this.initTop = -(this.halfHeight-this.gridIncrement)

    let ulElements = this.$el.getElementsByTagName('ul')
    for(let i = 0; i < ulElements.length; i++) {
      ulElements[i].style.top = this.initTop + 'px'
    }
  },
  methods: {
    startDrag: function (evt) {
      console.log('drag start')
      this.$dragElement = evt.target.parentNode
      console.log(this.evt)
      window.addEventListener(evt.type === "mousedown" ? "mousemove" : "touchmove", this.handleMouseMoveEvent)
      window.addEventListener(evt.type === 'mousedown' ? 'mouseup' : 'touchend', this.handleMouseUpEvent)
    },
    drag: function (evt) {
      this.mousePosition = this.findMousePositionRelativeWithRoot(evt);
      console.log(this.mousePosition)
    },
    dragEnd: function () {
      this.removeListener()
    },
    findMousePositionRelativeWithRoot: function (evt) {
      const c = evt.type.match(/^touch/) ? evt.touches[0] : evt
      let Mx = c.clientX,
          My = c.clientY,
          offsetX = Mx - this.$el.getBoundingClientRect().left,
          offsetY = My - this.$el.getBoundingClientRect().top

      console.log('check: ...', this.$el.getBoundingClientRect().top)

      return {x: offsetX, y: offsetY};
    },
    removeListener: function () {
      window.removeEventListener('mousemove', this.handleMouseMoveEvent)
      window.removeEventListener('touchmove', this.handleMouseMoveEvent)
      window.removeEventListener('mouseup', this.handleMouseUpEvent)
      window.removeEventListener('touchend', this.handleMouseUpEvent)
    },
    sendMessage(method) {
      if (typeof method === 'string') method = {method: method}
      window.parent.postMessage(method, '*')
      return false
    },
    resetGame: function () {
      this.points.filter(point => this.password.includes(point.id))
          .forEach(point => {
            this.$delete(point, "error")
            this.$delete(point, "correct")
          })
      this.password = []
      this.isDrawed = false;
    }
  }
}
</script>
