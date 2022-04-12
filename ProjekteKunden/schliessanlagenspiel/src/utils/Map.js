import Vue from 'vue'
import PathItem from '@/components/PathItem'
import Door from '@/components/Door'

export default class Map {
  constructor (app) {
    this.app = app
    this.filename = ''
    this.pathItems = []
    this.doors = []
    this.doorGroups = []
    this.disabledSeconds = 5 // Sekunden
    this.disabledDoorTimer = null
    this.groupNames = ['yellow', 'grey', 'blue', 'pink', 'green']
    this.scale = 1.0
    this.exitPathItemName = 'Path 13(reverse)'
  }

  /**
   * Lädt die Kartendaten aus der angebenen Json-Datei
   * @param filename Name der Json-Datei (ausgehend vom Verzeichnis "public/"
   * @returns {Promise<void>}
   */
  async load (filename) {
    this.scale = this.app.$el.clientWidth / 1920.0
    // // console.log('scale= ', this.scale)
    await fetch(filename)
      .then(response => response.json())
      .then((data) => { this.loadData(data) })
  }

  /**
   * Lädt die Pfade und Türen aus den übergebenen Json-Daten
   * @param data Json-Daten wie in Datei "map.json"
   */
  loadData (data) {
    this.loadPathItems(data)
    this.loadDoors(data)
    this.connectPathItems(data)
    this.app.updateDoorGroups()
  }

  /**
   * Lädt die Pfade aus den übergebenen Json-Daten
   * Erzeugt PathItem-Componenten (siehe compontents/PathItem.vue) und fügt diese ins HTML ein
   * @param data Json-Daten wie in Datei "map.json"
   */
  loadPathItems (data) {
    const PathItemClass = Vue.extend(PathItem)

    for (const i in data.paths) {
      const path = data.paths[i]
      const pathItem = new PathItemClass({
        propsData: {
          id: path.id,
          start: path.start,
          end: path.end,
          name: path.name,
          map: this,
          reverse: false
        }
      })
      pathItem.$mount()
      // this.app.$refs.gameWrapper.appendChild(pathItem.$el)
      this.pathItems[pathItem.id] = pathItem
    }

    for (const i in data.paths) {
      const path = data.paths[i]
      const pathItem = new PathItemClass({
        propsData: {
          id: path.id,
          start: path.end,
          end: path.start,
          name: path.name + '(reverse)',
          map: this,
          reverse: true
        }
      })
      this.pathItems.push(pathItem)
    }
  }

  /**
   * Lädt die Türen aus den übergebenen Json-Daten
   * Erzeugt Door-Componenten (siehe compontents/Door.vue) und fügt diese ins HTML ein
   * @param data Json-Daten wie in Datei "map.json"
   */
  loadDoors (data) {
    const DoorClass = Vue.extend(Door)

    for (const i in data.doors) {
      const door = data.doors[i]
      var doorItem = new DoorClass({
        propsData: {
          id: door.id,
          start: door.start,
          end: door.end,
          name: door.name,
          group: door.group,
          startOpen: door.open, // true, um alle Türen anfänglich auf "open" zu setzen
          map: this
        }
      })
      doorItem.$mount()
      this.app.$refs.doorsvg.appendChild(doorItem.$el)
      this.doors[doorItem.id] = doorItem
      if (!this.doorGroups[doorItem.group]) this.doorGroups[doorItem.group] = []
      this.doorGroups[doorItem.group].push(doorItem)
    }
  }

  /**
   * Stellt die Verbindungen zwischen den erzeugten PathItem- und Door-Componenten entsprechende der
   * übergebenen Json-Daten her.
   * @param data Json-Daten wie in Datei "map.json"
   */
  connectPathItems (data) {
    for (const i in this.pathItems) {
      const pathItem = this.pathItems[i]
      const path = data.paths[pathItem.id]
      const nextIds = pathItem.reverse ? path.prev : path.next
      pathItem.next = this.pathItems.filter((p) => {
        return p.id !== pathItem.id && nextIds.includes(p.id) && pathItem.end.x === p.start.x && pathItem.end.y === p.start.y
      })

      if (pathItem.next.length !== nextIds.length) {
        // // console.log('connectPathItems() nextIds:', nextIds, ', pathItem.next:', pathItem.next)
      }

      const door = (pathItem.reverse ? path.startDoor : path.endDoor)
      if (door !== null) pathItem.door = this.doors[door]
      pathItem.reversePath = pathItem.getReverse()
    }
  }

  /**
   * Sucht einen Pfad (PathItem) über den Namen
   * Die Namen werden in der Illustrator-Datei festgelegt; die Pfad-Elemente erhalten einen fortlaufenden Postfix (z.B. ".5")
   * Siehe map.json für die vorhandenen Pfad-Namen
   * @param name Name des Pfad-Elements
   * @returns {null|PathItem} PathItem oder null, wenn keine PathItem dieses Namens gefunden wurde
   */
  getPathItemByName (name) {
    for (const i in this.pathItems) {
      if (this.pathItems[i].name === name) return this.pathItems[i]
    }
    // console.error('Map.getPathItem(', name, ') pathItem could not be found!')
    return null
  }

  /**
   * Sucht Pfade (PathItems), die am angegebenen Punkt beginnen oder enden
   * @param point
   * @returns {[PathItems]}
   */
  getPathItemsByPoint (point) {
    const foundPathItems = []
    for (const i in this.pathItems) {
      if (this.isSamePoint(this.pathItems[i].start, point)) {
        foundPathItems.push(this.pathItems[i])
      }
    }
    return foundPathItems
  }

  /**
   * Vergleicht, ob zwei Punkte identisch sind.
   * @param p1 Erster Punkt
   * @param p2 Zweiter Punkt
   * @returns {boolean}
   */
  isSamePoint (p1, p2) {
    return p1 === p2 || (p1.x === p2.x && p1.y === p2.x)
  }

  /**
   * Erzeugt eine Route zwischen dem angegebenen Pfad und dem Ausgang
   * Berücksichtigt dabei die derzeit geschlossenen Türen. (Der Algorithmus berechnet einen Weg, auch wenn es keinen gäbe aufgrund geschlossener Türen.)
   * @param startPathItem Pfad-Objekt
   * @param person String 'girl' oder 'guard' muss an dieser Stelle übergeben werden
   * @returns {*[PathItem]}
   */
  createRoute (startPathItem, person) {
    // die für den Dijkstra-Algorithmus relevanten Eigenschaften der PathItems auf den Urpsrungszustand zurücksetzen
    // console.log(person, ' berechnet Route')
    this.pathItems.forEach(PathItem => {
      PathItem.routing[person].pathDist = Infinity
      PathItem.routing[person].optPrevPath = null
      PathItem.routing[person].numberPrevPaths = 0
      PathItem.routing[person].usesPath = false
    })
    const route = [] // kürzeste Route (PathItems) hier einfügen nachdem der kürzeste Weg berechnet wurde
    var targetPathItem = null
    if (person === 'guard') {
      targetPathItem = this.app.girl.pathItem
      if (targetPathItem === startPathItem || targetPathItem.reversePath === startPathItem) {
        // console.log(this.app.girl.percent, this.app.guard.percent)
        if (targetPathItem === startPathItem && this.app.girl.percent < this.app.guard.percent) {
          route.unshift(startPathItem.reversePath)
          return route
        } else if (targetPathItem.reversePath === startPathItem && (100 - this.app.girl.percent) > this.app.guard.percent) {
          route.unshift(startPathItem.reversePath)
          return route
        } else {
          route.unshift(startPathItem)
          return route
        }
      }
    } else {
      targetPathItem = this.getExitPathItem() // Ausgang
      if (targetPathItem === startPathItem) {
        route.unshift(targetPathItem)
        return route
      }
    }
    var nextPaths = []
    // vom Startpfad ausgehende Pfade ermitteln (vom Endpunkt des Startpfades aus)
    if (startPathItem.door && !startPathItem.door.isOpen) { // Sonderfall für die Neuberechnung der Route beim Treffen auf eine geschlossene Tür
      const reversePathItem = startPathItem.reversePath // von der Tür wieder weglaufen
      startPathItem = reversePathItem
      nextPaths = startPathItem.next // ein direkter Weg zu der geschlossenen Tür, welche eben gefunden wurde, ist ausgeschlossen
    } else { // wenn die Route zum ersten Mal berechnet wird oder wenn eine Tür angeklickt wurde und die Route neu berechnet wird
      nextPaths = startPathItem.next.slice()
      nextPaths.push(startPathItem.reversePath) // reverser Pfad (also Rückweg) wird hier mitbetrachtet
    }
    // Initialiserung der Priority Queue (das PathItem an Position 0 besitzt die geringste bisher berechnete Distanz zum Startknoten)
    var priorityQueue = []
    nextPaths.forEach(PathItem => {
      PathItem.routing[person].optPrevPath = startPathItem // der optimale vorherige Pfad ist hier der Startpfad
      PathItem.routing[person].numberPrevPaths = 1
      const distance = PathItem.getWeight(person) // Gewicht des Pfades ermitteln
      PathItem.routing[person].pathDist = distance
      // betrachteten Nachbarpfad in der Priority Queue aufnehmen
      if (priorityQueue.length === 0) {
        priorityQueue.push(PathItem)
      } else {
        var i = 0
        while (i < priorityQueue.length && priorityQueue[i].routing[person].pathDist < distance) {
          i++
        }
        priorityQueue.splice(i, 0, PathItem) // Einfügen an Stelle i (und 0 Elemente entfernen)
      }
    })

    // Eigentliche Berechnung beginnt. Algorithmus ist beendet wenn Priority Queue leer ist oder der Zielpfad an erster Stelle steht
    while (priorityQueue.length !== 0) {
      const PrioItem = priorityQueue.shift() // vorderstes PathItem aus der Queue entnehmen
      if (PrioItem === targetPathItem) {
        route.unshift(targetPathItem)
        break
      }
      if (PrioItem === targetPathItem.reversePath) {
        route.unshift(targetPathItem.reversePath)
        break
      }
      nextPaths = PrioItem.next // Nachbarpfade betrachten
      nextPaths.forEach(PathItem => {
        const numberPrev = PathItem.routing[person].numberPrevPaths
        PathItem.routing[person].numberPrevPaths = PrioItem.routing[person].numberPrevPaths + 1 // Anzahl Pfade zum PrioItem und dann zu diesem PathItem (+1)
        const distance = PathItem.getWeight(person) + PrioItem.routing[person].pathDist // Distanz zum Startpfad = Gewicht des betrachteten Pfades + Distanz vom Start bis zum vorherigen Pfad
        if (PathItem.routing[person].pathDist > distance) { // Wenn die neu berechnete Distanz besser ist, als der bisherige Wert, wird diese überschrieben und das PathItem in der Priority Queue aufgenommen
          PathItem.routing[person].pathDist = distance
          PathItem.routing[person].optPrevPath = PrioItem // Vorgänger-Pfad merken
          if (PathItem === targetPathItem) {
          }
          if (priorityQueue.length === 0) {
            priorityQueue.push(PathItem)
          } else {
            var i = 0
            while (i < priorityQueue.length && priorityQueue[i].routing[person].pathDist < distance) {
              i++
            }
            priorityQueue.splice(i, 0, PathItem)
          }
        } else {
          PathItem.routing[person].numberPrevPaths = numberPrev // auf vorherigen Wert zurücksetzen, falls es schon einen anderen besseren Pfad gab
        }
      })
    }
    // kürzesten Weg nun ermitteln:
    // // console.log('vorherige Pfade', targetPathItem.routing[person].numberPrevPaths)
    if (route.length === 0) {
      route.unshift(targetPathItem)
    }
    if (targetPathItem.reverse) {
      (targetPathItem.reversePath).routing[person].usesPath = true // den nicht reversen Pfad hier auf true setzen, da nur diese auf der Karte eingezeichnet werden
    } else {
      targetPathItem.routing[person].usesPath = true
    }
    // console.log('DISTANZ ZUM ZIEL', person, this.getPathItemNames([route[0]]), route[0].routing[person].pathDist)
    var optPrev = route[0].routing[person].optPrevPath // optimaler Vorgängerpfad
    while (optPrev !== startPathItem) {
      // // console.log('vorherige Pfade', optPrev.routing[person].numberPrevPaths)
      route.unshift(optPrev) // den Vorgängerpfad immer vorne in das Array einfügen, damit die Pfad-Reihenfolge dann von vorne nach hinten gelesen werden kann
      if (optPrev.reverse) {
        (optPrev.reversePath).routing[person].usesPath = true
      } else {
        optPrev.routing[person].usesPath = true
      }
      var next = optPrev.routing[person].optPrevPath
      optPrev = next
    }
    if (route[0].id !== startPathItem.id) {
      route.unshift(startPathItem)
    }
    if (startPathItem.reverse) {
      (startPathItem.reversePath).routing[person].usesPath = true
    } else {
      startPathItem.routing[person].usesPath = true
    }
    return route
  }

  /**
   * Erzeugt eine zufällige Route
   * @param startPathItem Erster Pfad (PathItem) in der Route
   * @param len Anzahl der Pfade in der Route
   * @returns {[PathItem]} Array von PathItems
   */
  createRandomRoute (startPathItem, len) {
    // Create random route from the startPathItem
    if (!len) len = 30
    const usedPathIds = []
    const route = []

    // aktuelles PathItem (oder reverse) hinzufügen
    let pathItem = startPathItem
    if (!pathItem.door || pathItem.door.isOpen) {
      route.push(pathItem)
      usedPathIds.push(pathItem.id)
    } else {
      pathItem = pathItem.getReverse()
      route.push(pathItem)
      usedPathIds.push(pathItem.id)
    }

    while (route.length < len && (!pathItem.door || pathItem.door.isOpen) && pathItem.next.length) {
      let next = pathItem.next.filter((p) => {
        return !usedPathIds.includes(p.id)
      })
      if (!next.length) next = pathItem.next
      pathItem = next[Math.floor(Math.random() * next.length)]
      route.push(pathItem)
      usedPathIds.push(pathItem.id)
    }
    // console.log('randomRoute() result:', route, 'length:', route.length)
    return route
  }

  disabledGroup (groupName) {
    const index = this.groupNames.indexOf(groupName)
    this.groupNames.splice(index, 1)
    // console.log(index, this.groupNames)
    this.disabledDoorTimer = setTimeout(() => {
      this.groupNames.splice(index, 0, groupName)
      // console.log(this.groupNames)
      this.disabledDoorTimer = null
    }, this.disabledSeconds * 1000)
  }

  toggleDoorGroup (groupName) {
    if (this.doorGroups[groupName]) {
      this.doorGroups[groupName].forEach((doorItem) => {
        doorItem.isOpen = !doorItem.isOpen
      })
      this.disabledGroup(groupName)
      // console.log('TOGGLE DOOR, NEUE ROUTE')
      this.app.girl.createRoute()
      this.app.guard.createRoute()
    }
  }

  autoToggleDoorGroup () {
    // console.log('AUTO', this.groupNames)
    const randomInt = Math.floor(Math.random() * this.groupNames.length)
    const randomGroup = this.groupNames[randomInt]
    // console.log('RANDOM NUMBER', randomInt, '; GROUP NAME', randomGroup)
    if (this.doorGroups[randomGroup]) {
      this.doorGroups[randomGroup].forEach((doorItem) => {
        doorItem.isOpen = !doorItem.isOpen
      })
      // console.log('Random Door Switch ')
      this.app.girl.createRoute()
      this.app.guard.createRoute()
    }
  }

  getPathItemIds (pathItems) {
    return pathItems.map(p => p.id)
  }

  getPathItemNames (pathItems) {
    return pathItems.map(p => p.name)
  }

  getExitPathItem () {
    return (this.getPathItemByName(this.exitPathItemName))
  }
}
