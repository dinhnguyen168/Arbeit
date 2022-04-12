<template>
  <div id="gameWrapper" @mousedown="touchstart($event)" @mouseup="touchend()" @touchstart="touchstart($event)" @touchend="touchend()" >
    <div id="registers">
      <register register="principal" @pressed="onRegisterChanged" :active="activeRegisters.includes('prinzipal')">Prinzipal</register>
      <register register="flute" @pressed="onRegisterChanged" :active="activeRegisters.includes('flute')">Flöte</register>
      <register register="trombone" @pressed="onRegisterChanged" :active="activeRegisters.includes('trombone')">Posaune</register>
    </div>
    <div id="chords">
      <note note="cDur" :chord="true" @pressed="onPressed" :nextNotePressed="pressedNotes.fDur">C Dur</note>
      <note note="fDur" :chord="true" @pressed="onPressed" :nextNotePressed="pressedNotes.gDur">F Dur</note>
      <note note="gDur" :chord="true" @pressed="onPressed" >G Dur</note>
    </div>
    <div id="notes">
      <note note="c"  @pressed="onPressed" :nextNotePressed="pressedNotes.d">C</note>
      <note note="d"  @pressed="onPressed" :nextNotePressed="pressedNotes.e">D</note>
      <note note="e"  @pressed="onPressed" :nextNotePressed="pressedNotes.f">E</note>
      <note note="f"  @pressed="onPressed" :nextNotePressed="pressedNotes.g">F</note>
      <note note="g"  @pressed="onPressed" :nextNotePressed="pressedNotes.a">G</note>
      <note note="a"  @pressed="onPressed" :nextNotePressed="pressedNotes.h">A</note>
      <note note="h"  @pressed="onPressed" :nextNotePressed="pressedNotes.c2">H</note>
      <note note="c2" @pressed="onPressed">C</note>
    </div>
    <div id="start-screen" :class="{show:showStartScreen}" @click="showStartScreen = false" @touchstart="showStartScreen = false"></div>
    <div v-if="!soundLoaded" id="wait">
      <img src="./img/wait.gif">
    </div>
  </div>
</template>

<script>

import { Howl } from 'howler'
import note from '@/components/note'
import register from '@/components/register'

export default {
  name: 'Game',
  data () {
    return {
      spriteLen: 5000, // Länge eines Sprites (ms)
      startLen: 1000, // Dauer bis der kontinuierliche Ton erreicht ist (ms)
      endLen: 2000, // Endbereich in dem der Ton ausklingt (ms)

      registers: { principal: 0, flute: 40, trombone: 80, tutti: 120 }, // Offsets für die Sprites der Register (s)
      notes: { c: 0, d: 5, e: 10, f: 15, g: 20, a: 25, h: 30, c2: 35 }, // Offsets für die Noten in einem Register (s)

      chordOffset: 160, // Offsets für das Akkord-Register (s)
      chords: { cDur: 0, fDur: 5, gDur: 10 }, // Offsets für die Akkord-Töne (s)

      activeRegisters: ['flute'], // Voreingestellte Register

      sprites: null,
      pressedNotes: { c: false, d: false, e: false, f: false, g: false, a: false, h: false, c2: false, cDur: false, fDur: true, gDur: false },
      sounds: null,
      playedSprites: [],
      soundLoaded: false,
      showStartScreen: true
    }
  },
  components: {
    note,
    register
  },
  created () {
    console.log('pressedNotes', this.pressedNotes)

    this.sprites = {}
    for (const [register, registerOffset] of Object.entries(this.registers)) {
      for (const [note, offset] of Object.entries(this.notes)) {
        const name = register + '_' + note
        this.sprites[name] = [(registerOffset + offset) * 1000, this.spriteLen, false]
        this.sprites[name + '_mid'] = [(registerOffset + offset) * 1000 + this.startLen, this.spriteLen - this.startLen, false]
        this.sprites[name + '_end'] = [(registerOffset + offset) * 1000 + this.spriteLen - this.endLen, this.endLen, false]
      }
    }

    for (const [note, offset] of Object.entries(this.chords)) {
      const name = 'chord_' + note
      this.sprites[name] = [(this.chordOffset + offset) * 1000, this.spriteLen, false]
      this.sprites[name + '_mid'] = [(this.chordOffset + offset) * 1000 + this.startLen, this.spriteLen - this.startLen, false]
      this.sprites[name + '_end'] = [(this.chordOffset + offset) * 1000 + this.spriteLen - this.endLen, this.endLen, false]
    }
    // console.log('sprites:', this.sprites)
    this.sounds = new Howl({
      src: [require('./assets/organ.mp3')],
      sprite: this.sprites,
      mute: true,
      preload: true
    })
  },
  mounted () {
    this.sounds.once('load', () => {
      this.soundLoaded = true
      this.sounds.mute(false)
      this.sounds.volume(1.0)
    })
  },
  methods: {
    onPressed (note, pressed, isChord) {
      if (this.soundLoaded) {
        // console.log('Note', note, pressed ? 'pressed' : 'released')
        this.pressedNotes[note] = pressed
        if (pressed) {
          this.playKey(note, isChord)
        } else {
          this.stopKey(note, isChord)
        }
      }
    },
    playKey (note, isChord) {
      console.log('playKey()', note, isChord)
      this.playedSprites[note] = []
      const registers = (isChord ? ['chord'] : this.activeRegisters.length === 3 ? ['tutti'] : this.activeRegisters)
      registers.forEach((register) => {
        const sprite = register + '_' + note
        console.log('play sprite', sprite)
        const id = this.sounds.play(sprite)
        this.playedSprites[note][sprite] = id
        this.sounds.once('end', () => {
          delete this.playedSprites[note][sprite]
        }, id)

        const continueNote = () => {
          if (this.playedSprites[note][sprite]) {
            const playerId = this.playedSprites[note][sprite]
            const pos = this.sounds.seek(null, playerId)
            const newPos = (this.sprites[sprite][0] + this.startLen) / 1000
            console.log('still playing ', note, ', pos=', pos, ', rewind to', newPos)
            const id = this.sounds.play(sprite + '_mid')
            this.playedSprites[note][sprite] = id
            this.sounds.fade(0.0, 1.0, 200, id)
            this.sounds.fade(1.0, 0.0, 500, playerId)
            setTimeout(() => {
              this.sounds.stop(playerId)
            }, 500)
            setTimeout(continueNote, this.spriteLen - this.startLen - this.endLen - 200)
          }
        }
        setTimeout(continueNote, this.spriteLen - this.endLen - 200)
      })
    },
    stopKey (note, isChord) {
      console.log('stopKey()', note, isChord)
      for (const [sprite, playerId] of Object.entries(this.playedSprites[note])) {
        delete this.playedSprites[note][sprite]
        if (playerId > 0) {
          const pos = this.sounds.seek(null, playerId) * 1000 - this.sprites[sprite][0]
          if (pos < this.spriteLen - this.endLen) {
            console.log('play end note', note, 'pos=', pos, ', spriteLen-end=', this.spriteLen - this.endLen)
            const endId = this.sounds.play(sprite + '_end')
            this.sounds.fade(0.0, 1.0, 50, endId)
            this.sounds.fade(1.0, 0.0, 100, playerId)
            setTimeout(() => {
              this.sounds.stop(playerId)
            }, 200)
          }
        }
      }
    },
    onRegisterChanged (register, on) {
      var index = this.activeRegisters.indexOf(register)
      if (on) {
        if (index === -1) {
          this.activeRegisters.push(register)
        }
      } else if (index !== -1) {
        this.activeRegisters.splice(index, 1)
      }
      console.log('activeRegisters:', this.activeRegisters)
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
      event.preventDefault()
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
  },
  computed: {
  }
}
</script>
