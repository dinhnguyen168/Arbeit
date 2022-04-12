<template>
  <svg v-if="!reverse" :width="width" :height="height" :style="{left: left, top: top}" :data-name="name">
    <line :x1="x1" :y1="y1" :x2="x2" :y2="y2" :style="{'stroke-width': strokeWidth, stroke: color}" :class="{ path:true, show:showPath }" />
  </svg>
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
    reverse: {
      type: Boolean,
      default: false
    },
    reversePath: Object
  },
  data () {
    return {
      len: 0,
      door: null, // Tür am Ende des PathItems (bei this.end)
      next: [], // Verbindungen am Ende des PathItems (abgehend von this.end)
      strokeWidth: 1,
      showPath: false,
      showPathColor: false, // pfade anzeigen zum entwickeln
      routing: { // für die Berechnung der Route mit dem Dijkstra-Algorithmus
        girl: {
          pathDist: Infinity,
          optPrevPath: null,
          numberPrevPaths: 0,
          usesPath: false
        },
        guard: {
          pathDist: Infinity,
          optPrevPath: null,
          numberPrevPaths: 0,
          usesPath: false
        }
      }
    }
  },
  created () {
    this.len = Math.round(Math.sqrt(Math.pow(this.start.y - this.end.y, 2) + Math.pow(this.start.x - this.end.x, 2)))
    if (!this.name.length) this.name = '' + this.id
  },
  methods: {
    /**
     * Prüft, ob ein Punkt Bestandteil dieses PathItems ist
     * Es wird nur mit dem Start- und End-Punkt verglichen.
     * @param point Zu prüfender Punkt
     * @returns {boolean} Der Punkt ist Bestandteil dieses PathItems
     */
    containsPoint (point) {
      return (this.map.isSamePoint(this.start, point) || this.map.isSamePoint(this.end, point))
    },
    /**
     * Liefert den umgekehrten Pfad. Dieser hat dieselbe id, der Name enthält aber 'reverse'
     * @returns {*}
     */
    getReverse () {
      const id = this.id
      const reverse = this.reverse
      const pathItem = this.map.pathItems.find((p) => {
        return p.id === id && p.reverse !== reverse
      })
      return pathItem
    },
    getWeight (person) {
      if (this.door === null) return (this.len)
      else if (this.door.isOpen) return (this.len) // Wenn eine Tür vorhanden ist und offen ist: Gewicht entspricht der Pfadlänge
      else return (this.len + (5000 / this.routing[person].numberPrevPaths)) // Geschlossene Tür: Gewicht wird erhöht in Abhängigkeit der Anzahl der vorherigen Pfade oder this.len + 1000 oder this.len * (100 / this.routing[person].numberPrevPaths)
    }
  },
  computed: {
    height () {
      return Math.round(Math.max(this.strokeWidth + 1, Math.abs(this.start.y - this.end.y) * this.map.scale) * 10) / 10 + 'px'
    },
    width () {
      return Math.round(Math.max(this.strokeWidth + 1, Math.abs(this.start.x - this.end.x) * this.map.scale) * 10) / 10 + 'px'
    },
    x1 () {
      return this.start.x <= this.end.x ? 0 : this.width
    },
    x2 () {
      return this.start.x > this.end.x ? 0 : this.width
    },
    y1 () {
      return this.start.y <= this.end.y ? 0 : this.height
    },
    y2 () {
      return this.start.y > this.end.y ? 0 : this.height
    },
    left () {
      return Math.round((Math.min(this.start.x, this.end.x) * this.map.scale) * 10) / 10 + 'px'
    },
    top () {
      return Math.round((Math.min(this.start.y, this.end.y) * this.map.scale) * 10) / 10 + 'px'
    },
    color () {
      return this.showPathColor ? (((this.routing.girl.usesPath && this.routing.guard.usesPath)) ? 'rgba(255, 0, 255, 0.5)' : this.routing.girl.usesPath ? 'rgba(0, 0, 255, 0.5)' : this.routing.guard.usesPath ? 'rgba(204, 51, 0, 0.5)' : 'rgba(204, 204, 204, 0.3)') : 'transparent'
    },
    isExit () {
      return this.name === this.map.exitPathItemName
    }
  }
}
</script>

<style scoped>

</style>
