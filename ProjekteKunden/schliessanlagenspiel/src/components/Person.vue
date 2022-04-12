<template>
  <div :class="{person: true, guard: isGuard}" :style="{left: left, top: top}"></div>
</template>

<script>
export default {
  name: 'Person',
  props: {
    map: Object,
    speed: Number,
    startPathItem: null,
    role: null,
    followPerson: {
      type: Object,
      default: null
    }
  },
  data () {
    return {
      isGuard: false,
      percent: 0.0,
      pathItem: Object,
      nextPathItems: [],
      intervalTimer: null,
      nextDoorChecked: false
    }
  },
  created () {
    this.isGuard = (this.followPerson !== null)
    this.pathItem = this.startPathItem
    this.percent = 100 // die Figur wird auf den Endpunkt des Startpfades gesetzt
    this.speed = 0.01 + this.speed
  },
  mounted () {
  },
  methods: {
    /**
     * Wenn ein aktueller Pfad vorhanden ist, wird mit der Bewegung gestartet.
     * Im Interval wird this.onInterval aufgerufen.
     */
    startMoving () {
      if (this.pathItem) {
        // console.log(this.role, 'startMoving()')
        if (this.intervalTimer) clearInterval(this.intervalTimer)
        this.intervalTimer = setInterval(() => {
          this.onInterval()
        }, 50)
      }
    },
    /**
     * Beendet die Bewegung
     */
    stopMoving () {
      // console.log(this.role, 'stopMoving()')
      clearInterval(this.intervalTimer)
      this.intervalTimer = null
    },
    /**
     * Wird im Intervall für jeden "Schritt" aufgerufen.
     * Setzt die Position (this.percent des aktuellen Pfades) weiter.
     * Prüft, ob das Ende des aktuellen Pfades (this.pathItem) erreicht ist.
     * Prüft, ob die nächste Tür fast erreicht und diese geschlossen ist.
     * Der Guard prüft, ob einen Kollision der Personen vorliegt.
     */
    onInterval () {
      const stopDistanceBeforeClosedDoor = 15
      if (this.pathItem) {
        if (this.percent === 100) {
          this.onEndOfPathItem()
        } else {
          const newLen = this.percent * this.pathItem.len / 100.0 + this.speed
          this.percent = Math.min(100.0, 100.0 / this.pathItem.len * newLen)

          if (this.followPerson && this.followPerson.percent === 0) {
            // console.log(this.role, 'Mädchen ist auf neuem PathItem, RECHNE NEUE ROUTE!!!')
            this.createRoute()
          }
          if (!this.nextDoorChecked && this.pathItem.len - newLen < stopDistanceBeforeClosedDoor) {
            if (!this.checkNextDoor()) return
          }

          if (this.followPerson) this.checkCollision()
        }
      } else {
        // console.log('onInterval() No pathItem: stopMoving()')
        this.stopMoving()
      }
    },
    /**
     * Prüft, ob die nächste Tür geschlossen ist und hält in diesem Fall an.
     */
    checkNextDoor () {
      const door = this.pathItem.door
      if (door && !door.isOpen) {
        // console.log(this.role, 'checkNextDoor()', this.pathItem.name, ' door ', door.id, ' is closed')
        this.onClosedDoor()
        // return false
      }
      return true
    },
    onClosedDoor () {
      if (!this.pathItem.isExit) {
        const pathItems = this.map.createRoute(this.pathItem, this.role)
        // console.log('onClosedDoor() new route ', this.map.getPathItemNames(pathItems))
        this.setRoute(pathItems)
      }
    },
    /**
     * Prüft, ob eine Kollision mit der this.followPerson vorliegt.
     */
    checkCollision () {
      const maxDistance = 12
      if (this.followPerson && this.followPerson.pathItem.id === this.pathItem.id) {
        const dist2 = Math.pow(parseFloat(this.left) - parseFloat(this.followPerson.left), 2) + Math.pow(parseFloat(this.top) - parseFloat(this.followPerson.top), 2)
        // console.log(this.role, ': checkCollision() dist:', Math.round(Math.sqrt(dist2) * 10) / 10)
        if (dist2 < maxDistance * maxDistance) this.onCollision()
      }
    },
    /**
     * Reagiere auf eine Kollision der Personen.
     */
    onCollision () {
      // console.log(this.role, ': onCollision() stopMoving')
      this.stopMoving()
      this.followPerson.stopMoving()
      this.map.app.stopGame()
    },
    /**
     * Ende des aktuellen Pfades ist erreicht
     * Wenn an der entsprechenden Seite eine geschlossene Tür ist, wird angehalten.
     * Abhängig von der Laufrichtung und dem nächten Pfad-Element wird zunächst auf dem gleichen Pfad zurück gelaufen.
     */
    onEndOfPathItem () {
    // const door = this.pathItem.door
      if (this.pathItem.isExit) {
        // console.log(this.role, 'onEndOfPathItem(', this.pathItem.name, ') reached exit')
        this.stopMoving()
        this.map.app.stopGame()
        return
      }
      // if (door && !door.isOpen) {
      //  // console.log(this.isGuard ? 'guard' : 'girl', 'onEndOfPathItem(', this.pathItem.name, ') door ', door.id, ' is closed, so stop')
      //  this.stopMoving()
      // }
      if (this.nextPathItems.length) {
        this.pathItem = this.nextPathItems.shift()
        this.percent = 0.0
      } else {
        // console.log(this.role, 'onEndOfPathItem() end of path reached; create new path')
        const pathItems = this.map.createRoute(this.pathItem, this.role)
        this.setRoute(pathItems)
      }
    },
    /**
     * Erzeugt eine zufällige Route ab dem aktuellen Pfad
     * Berücksichtigt dabei keine geschlossenen Türen
     * Setzt diese Route und startet dadurch die Bewegung
     */
    createRandomRoute () {
      this.setRoute(this.map.createRandomRoute(this.pathItem))
    },
    /**
     * Erzeugt eine Route von der aktuellen Position zu dem angegebenen Punkt.
     * Berücksichtigt dabei die derzeit geschlossenen Türen.
     * Der angegebene Zielpunkt  muss der Start oder Endpunkt eines PathItems sein!
     * @param targetPoint ZielPunkt {x: 999, y:999} targetPoint
     */
    createRoute () {
      const pathItems = this.map.createRoute(this.pathItem, this.role)
      if (pathItems && pathItems.length) {
        this.setRoute(pathItems)
      } else {
        // console.log(this.role, 'createRoute : No route to target point!')
      }
    },
    setRoute (pathItems) {
      if (pathItems.length) {
        // console.log(this.role, 'setRoute(', this.map.getPathItemNames(pathItems), '), currentPathItem:', this.pathItem.name)
        const continueMoving = (this.pathItem.id === pathItems[0].id)
        if (continueMoving && this.pathItem.reverse !== pathItems[0].reverse) this.percent = 100.0 - this.percent
        this.pathItem = pathItems.shift()
        this.nextPathItems = pathItems
        // console.log('continueMoving:', (continueMoving ? 'true' : 'false'))
        if (!continueMoving) this.percent = 0
        this.startMoving()
      }
    }
  },
  computed: {
    left () {
      return Math.round((this.pathItem.start.x + (this.pathItem.end.x - this.pathItem.start.x) * this.percent / 100.0) * this.map.scale * 10) / 10 + 'px'
    },
    top () {
      return Math.round((this.pathItem.start.y + (this.pathItem.end.y - this.pathItem.start.y) * this.percent / 100.0) * this.map.scale * 10) / 10 + 'px'
    }
    // role () {
    //   if (this.isGuard) return ('guard')
    //   else return ('girl')
    // }
  },
  watch: {
    pathItem (newPathItem) {
      // console.log(this.role, newPathItem.name)
      this.nextDoorChecked = false
    }
  }
}
</script>

<style scoped>

</style>
