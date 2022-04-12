<template>
  <div id="gameWrapper" ref="gameWrapper" @mousedown="touchstart($event)" @mouseup="touchend()" @touchstart="touchstart($event)" @touchend="touchend()" >
    <svg ref="doorsvg" id="doors"></svg>
    <div id="switches" ref="switches">
      <doorswitch v-for="(name, index) in doorGroups" :key="name" :groupName="name" :keyboard="(index+1).toString()" :app="app" v-on:toggle="toggleDoorGroup"></doorswitch>
    </div>
    <div id="gameEnd" v-if="gameEnd">
      <h1>Hier kommt ein Video</h1>
    </div>
    <div id="gameStart" v-if="gameStart">
      <input ref="inputCode" type="text" autofocus v-model="code">
    </div>
  </div>
</template>

<script>
import Person from '@/components/Person'
import Vue from 'vue'
import Map from '@/utils/Map'
import doorswitch from '@/components/DoorSwitch'
import { Sound } from '@/utils/sound'

export default {
  name: 'Schliessanlage',
  components: {
    doorswitch
  },
  data () {
    return {
      map: new Map(this),
      gameStart: true,
      gameEnd: false,
      restartTimer: null,
      restartInSeconds: 5, // Sekunden, später Länge des Videos
      guard: null,
      girl: null,
      doorGroups: [],
      autoToggleSeconds: 8,
      code: '',
      CORRECT_CODE: '1245'
    }
  },
  created () {
    window.START_TIME = 1.5 // in Sekunden
    window.FINISHED_TIME = 2.5 // in Sekunde
    this.autoToggleInterval = setInterval(() => {
      if (!this.gameStart && !this.gameEnd) {
        this.autoToggleDoorGroup()
      }
    }, this.autoToggleSeconds * 1000)
  },
  mounted () {
    const filename = 'map.json'
    this.map.load(filename).then(() => {
      this.createPersons()
    })
    this.sound = new Sound()
    this.sound.play('start')
  },
  watch: {
    code: function (newVal) {
      this.sound.play('number_enter')
      if (newVal === this.CORRECT_CODE) {
        this.startGame()
      }
      if (newVal.length === this.CORRECT_CODE.length && newVal !== this.CORRECT_CODE) {
        this.code = ''
      }
    }
  },
  computed: {
    app: function () {
      return this
    }
  },
  methods: {
    startGame () {
      this.sound.play('background')
      this.gameStart = false
      this.girl.pathItem = this.girl.startPathItem
      this.guard.pathItem = this.guard.startPathItem
      this.girl.createRoute()
      this.guard.createRoute()
    },
    stopGame () {
      this.sound.play('finished')
      this.gameEnd = true
      this.restartTimer = setTimeout(() => {
        this.sound.stop()
        this.gameStart = true
        this.gameEnd = false
        this.code = ''
        this.$nextTick(() => {
          this.$refs.inputCode.focus()
        })
      }, this.restartInSeconds * 1000)
    },
    updateDoorGroups () {
      this.doorGroups = Object.keys(this.map.doorGroups)
    },
    toggleDoorGroup (groupName) {
      this.sound.play('button_click')
      this.map.toggleDoorGroup(groupName)
    },
    autoToggleDoorGroup () {
      this.sound.play('door_close')
      this.map.autoToggleDoorGroup()
    },

    /**
     * Erzeugt die Spielfiguren, erzeugt Route und startet die Bewegung der Figuren.
     */
    createPersons () {
      const PersonClass = Vue.extend(Person)

      // console.log('create girl')
      this.girl = new PersonClass({
        propsData: {
          startPathItem: this.map.getPathItemByName('Path 27'),
          // startPathItem: this.map.getPathItemByName('Path 4'),
          // startPathItem: this.map.getPathItemByName('Path 14'),
          // startPathItem: this.map.pathItems[Math.floor(Math.random() * this.map.pathItems.length)],
          speed: 2,
          role: 'girl',
          map: this.map
        }
      })
      this.girl.$mount()
      this.$refs.gameWrapper.insertBefore(this.girl.$el, this.$refs.switches.nextSibling)
      // this.girl.createRoute()
      // console.log('girl pathitem', this.girl.startPathItem)

      // console.log('create guard')
      this.guard = new PersonClass({
        propsData: {
          startPathItem: this.map.getPathItemByName('Path 13'),
          // startPathItem: this.map.pathItems[Math.floor(Math.random() * this.map.pathItems.length)],
          speed: 2.5,
          role: 'guard',
          map: this.map,
          followPerson: this.girl
        }
      })
      this.guard.$mount()
      this.$refs.gameWrapper.insertBefore(this.guard.$el, this.$refs.switches.nextSibling)
      // this.guard.createRoute()
    },
    delayPreloadImages () {
      const imgs = document.querySelectorAll('img')
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
    sendMessage: function (method) {
      if (typeof method === 'string') method = { method: method }
      window.parent.postMessage(method, '*')
      return false
    },
    ignore: function (event) {
      event.stopPropagation()
      // event.preventDefault()
    },
    touchstart: function (event) {
      this.touchStarted = event
      // event.preventDefault()
      return this.sendMessage('touchstart')
    },
    touchend: function () {
      // event.preventDefault()
      if (this.touchStarted) {
        this.touchStarted = null
        setTimeout(() => {
          this.sendMessage('touchend')
        }, 100)
        return false
      }
    }
  }
}
</script>
