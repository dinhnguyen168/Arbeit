<template>
  <div class="hour-glass" v-if="show" @click="onClick">
    <div class="milk-glass">
      <div class="milk" :style="{ height: milkHeight }">
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Timer',
  props: {
    sound: Object
  },
  data () {
    return {
      maxFilled: 100, // percent
      percent: 0,
      startTime: 0,
      duration: 0,
      totalHeight: 13.5,
      show: false,
      intervalTimer: null,
      timerInterval: 50,
      countDownSoundStarted: false,
      pausedByUserAt: 0
    }
  },
  methods: {
    onClick () {
      if (this.pausedByUserAt) {
        this.startTime += (Date.now() - this.pausedByUserAt)
        this.unPauseTimer()
        this.pausedByUserAt = 0
      } else {
        this.pauseTimer()
        this.pausedByUserAt = Date.now()
      }
    },
    startTimer (duration, maxFilled) {
      if (this.sound) this.sound.stop('countdown')
      this.countDownSoundStarted = false
      this.maxFilled = (maxFilled || 100)
      this.percent = 0
      this.startTime = Date.now()
      this.duration = duration * 1000
      if (this.intervalTimer) clearInterval(this.intervalTimer)
      this.intervalTimer = setInterval(() => {
        this.onInterval()
      }, this.timerInterval)
      this.show = true
      this.pausedByUserAt = 0
    },
    pauseTimer () {
      clearInterval(this.intervalTimer)
      this.intervalTimer = null
      this.sound.stop('countdown')
    },
    unPauseTimer () {
      clearInterval(this.intervalTimer)
      this.intervalTimer = setInterval(() => {
        this.onInterval()
      }, this.timerInterval)
    },
    stopTimer () {
      clearInterval(this.intervalTimer)
      this.intervalTimer = null
      this.sound.stop('countdown')
      this.show = false
    },
    getPercent () {
      return this.percent
    },
    onInterval () {
      const dur = Math.min(Date.now() - this.startTime, this.duration)
      // console.log('dur', dur)
      this.percent = dur * 100.0 / this.duration
      // console.log('onInterval() dur=', dur, ', duration=', this.duration, ', percent=', this.percent)
      if (dur >= this.duration) {
        console.log('MilkGlass.onInterval() Timer abgelaufen. start:', this.startTime, ', now: ', Date.now())
        clearInterval(this.intervalTimer)
        this.intervalTimer = null
        this.$emit('timeout')
      } else {
        if (!this.countDownSoundStarted && this.duration - dur <= 4000) {
          this.sound.play('countdown')
          this.countDownSoundStarted = true
        }
      }
    }
  },
  computed: {
    milkHeight () {
      const height = Math.max(0, 100 - this.percent) / 100.0 * (this.maxFilled * this.totalHeight / 100.0) + 'rem'
      // console.log('milkHeight:', height)
      return height
    }
  }
}
</script>

<style scoped>

</style>
