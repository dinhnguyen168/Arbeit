<template>
  <line :x1="x1" :y1="y1" :x2="x2" :y2="y2" :style="{stroke: color, 'stroke-width': strokeWidth, opacity: transperency}" />
</template>

<script>
export default {
  name: 'PathItem',
  props: {
    id: Number,
    start: Object,
    end: Object,
    map: Object,
    name: String,
    group: String,
    startOpen: Boolean
  },
  data () {
    return {
      isOpen: this.startOpen,
      strokeWidth: 4
    }
  },
  created () {
    if (!this.name.length) this.name = '' + this.id
  },
  methods: {
  },
  computed: {
    color () {
      if (this.group === 'yellow') return ('rgb(255, 204, 0)')
      else if (this.group === 'pink') return ('rgb(255, 105, 180)')
      else if (this.group === 'blue') return ('rgb(0, 74, 255)')
      else if (this.group === 'red') return ('rgb(186, 0, 50)')
      else if (this.group === 'grey') return ('rgb(0, 255, 255)') // (150, 160, 170)
      else return ('rgb(0, 205, 0)')
    },
    transperency () {
      return this.isOpen ? 1.0 : 1.0
    },
    x1 () {
      return Math.round(this.start.x * this.map.scale) + 'px'
    },
    x2 () {
      if (this.isOpen) {
        // 7 * Math.PI / 4 entspricht dem Bogenmaß für 45 Grad
        // 5 * Math.PI / 3 entspricht 60 Grad
        const x2 = this.start.x + ((this.end.x - this.start.x) * Math.cos(5 * Math.PI / 3)) - ((this.end.y - this.start.y) * Math.sin(5 * Math.PI / 3))
        return Math.round(x2 * this.map.scale) + 'px'
      } else {
        return Math.round(this.end.x * this.map.scale) + 'px'
      }
    },
    y1 () {
      return Math.round(this.start.y * this.map.scale) + 'px'
    },
    y2 () {
      if (this.isOpen) {
        const y2 = this.start.y + ((this.end.x - this.start.x) * Math.sin(5 * Math.PI / 3)) + ((this.end.y - this.start.y) * Math.cos(5 * Math.PI / 3))
        return Math.round(y2 * this.map.scale) + 'px'
      } else {
        return Math.round(this.end.y * this.map.scale) + 'px'
      }
    }
  }
}
</script>

<style scoped>

</style>
