import { Howl } from 'howler'

class Sound {
  constructor () {
    this.sprites = {
      start: [0, window.START_TIME * 1000, false], // spielstart
      number_enter: [29000, 1000, false], // nummerneingabe
      background: [18000, 7000, true], // background
      door_close: [30000, 1000, false], // türen klappen zu
      button_click: [2000, 1500, false], // button drücken
      change_direction: [4000, 1500, false], // richtungsänderung bei blauer figur
      finished: [26000, window.FINISHED_TIME * 1000, false] // game finished
    }

    this.init()
    this.soundIds = {}
  }

  init () {
    console.log('init sound')
    this.howl = new Howl({
      src: [require('@/assets/schliessanlagenspiel.mp3')],
      sprite: this.sprites,
      mute: true,
      preload: true
    })
    const onSoundLoaded = () => {
      console.log('sound loaded')
      this.howl.mute(false)
      this.howl.volume(1.0)
    }
    this.howl.once('load', onSoundLoaded)
    if (this.howl.loaded) onSoundLoaded()
  }

  play (scenario) {
    console.log('sound.play()', scenario)
    if (this.sprites[scenario]) {
      const soundId = this.howl.play(scenario)
      switch (scenario) {
        case 'background':
          this.howl.volume(0.2, soundId)
          break
      }
      this.soundIds[scenario] = soundId
    } else {
      console.log('unknown sound scenario ', scenario)
    }
  }

  stop (sound) {
    console.log('sound.stop()', sound)
    if (this.soundIds[sound]) sound = this.soundIds[sound]
    this.howl.stop(sound)
  }
}

export { Sound }
