<template>
<div :class="{'switch':true }">
  <vue-ellipse-progress :progress="disabledProgress" :animation="'default 1 1'" :size="60" :thickness="5" :legend="false" :color="groupColor" />
  <svg height="60" width="60" :class="classNames" @click="toggle()">
   <circle cx="30px" cy="30px" r="22px" fill="red" /> <!-- stroke="black" stroke-width="3" -->
  </svg>
</div>
</template>

<script>
import { VueEllipseProgress } from 'vue-ellipse-progress'

export default {
  name: 'doorswitch',
  components: { VueEllipseProgress },
  props: {
    groupName: String,
    keyboard: String,
    app: Object
  },
  data () {
    return {
      switchedOn: false,
      disabled: false,
      disabledTimer: null, // Timout-Object von setTimeout
      // autoSwitchTimer: null,
      disabledInterval: null, // Timout-Object von setTimeout
      // autoSwitchSeconds: 10, // seconds
      delayDisabledSeconds: 5, // seconds
      disabledProgress: 0 // Prozent
      // autoToggleIntervall: null,
      // autoToggleSeconds: 5
    }
  },
  created () {
    // console.log(this.keyboard)
    document.addEventListener('keydown', (e) => {
      if (e.key === this.keyboard) {
        // console.log(e)
        this.toggle()
        // clearInterval(this.autoToggleInterval)
        // this.autoToggle()
      }
    })
    // this.autoToggle()
  },
  methods: {
    toggle () {
      if (!this.disabled && !this.app.gameStart) {
        this.switchedOn = !this.switchedOn
        this.setDisabled()
        // console.log('toggle()', this.groupName)
        this.$emit('toggle', this.groupName)
        // // console.log('AUTOSWITCHTIMER', this.autoSwitchTimer)
        /* if (this.autoSwitchTimer) clearTimeout(this.autoSwitchTimer) // Türengruppe ändert ihren Zustand automatisch wieder nach autoSwitchSeconds
        this.autoSwitchTimer = setTimeout(() => {
          this.switchedOn = !this.switchedOn
          // console.log('automatic', 'toggle()', this.groupName)
          this.$emit('toggle', this.groupName)
          // this.autoSwitchTimer = null
        }, this.autoSwitchSeconds * 1000) */
      }
    },
    /* autoToggle () {
      this.autoToggleInterval = setInterval(() => {
        this.switchedOn = !this.switchedOn
        this.$emit('toggle', this.groupName)
      }, this.autoToggleSeconds * 1000)
    }, */
    setDisabled () {
      this.disabled = true
      if (this.disabledTimer) clearTimeout(this.disabledTimer)
      this.disabledStartTime = Date.now()
      this.disabledTimer = setTimeout(() => {
        this.disabled = false
        this.disabledTimer = null
      }, this.delayDisabledSeconds * 1000)
      this.disabledInterval = setInterval(() => {
        if (this.disabled) {
          var progress = Date.now() - this.disabledStartTime
          this.disabledProgress = Math.min(progress / (this.delayDisabledSeconds * 1000), 1.0) * 100
          // // console.log(this.disabledProgress)
        } else {
          clearInterval(this.disabledInterval)
          this.disabledProgress = 0
        }
      }, 20)
    }
  },
  computed: {
    classNames () {
      return 'button ' + this.groupName + (this.disabled ? ' disabled' : '')
    },
    groupColor () {
      if (this.groupName === 'yellow') return 'rgb(255, 204, 0)'
      else if (this.groupName === 'green') return 'rgb(0, 205, 0)'
      else if (this.groupName === 'blue') return 'rgb(0, 74, 255)'
      else if (this.groupName === 'grey') return 'rgb(0, 255, 255)'
      else if (this.groupName === 'pink') return 'rgb(255, 105, 180)'
      else return '#3f79ff'
    }
  }
}
</script>

<style scoped>

</style>
