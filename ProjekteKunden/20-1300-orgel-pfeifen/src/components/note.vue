<template>
  <div :class="{ key: true, note: !chord, chord: chord }" @mousedown="pressed = true" @touchstart="pressed = true" @touchend="pressed = false">
    <img :src="url">
    <div class="label"><span><slot></slot></span></div>
  </div>
</template>

<script>
export default {
  name: 'note',
  props: {
    note: String,
    chord: {
      default: false,
      required: false
    },
    nextNotePressed: {
      default: false,
      required: false
    }
  },
  data () {
    return {
      pressed: false
    }
  },
  computed: {
    url () {
      return require('../img/' + (this.chord ? 'chord' : 'note') + '-' + (this.pressed ? 'down' + (this.nextNotePressed ? '' : '-shadow') : 'up') + '.png')
    }
  },
  watch: {
    pressed (val) {
      this.$emit('pressed', this.note, val, this.chord)
    }
  },
  mounted () {
    document.addEventListener('mouseup', () => {
      this.pressed = false
    })
  }
}
</script>
