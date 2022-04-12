<template>
  <div :class="{ register: true, pressed: pressed }" @mousedown="pressed = !pressed" @touchstart="pressed = !pressed">
    <img :src="url">
    <div class="label"><span><slot></slot></span></div>
  </div>
</template>

<script>
export default {
  name: 'register',
  props: {
    register: String,
    active: {
      default: false,
      required: false
    }
  },
  data () {
    return {
      pressed: false
    }
  },
  created () {
    this.pressed = this.active
  },
  computed: {
    url () {
      return require('../img/register-' + (this.pressed ? 'down' : 'up') + '.png')
    }
  },
  watch: {
    pressed (val) {
      this.$emit('pressed', this.register, val)
    }
  }
}
</script>
