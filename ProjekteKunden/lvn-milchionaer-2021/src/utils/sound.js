import { Howl } from 'howler'

class Sound {
  constructor () {
    this.sprites = { // Prefixes: a - answer, p - player, q - question, s - sound
      correct: [0, 1500, false], // correct answer
      incorrect: [2000, 1500, false], // incorrect answer
      changePlayer: [4000, 1500, false], // player changed
      risk: [6000, 4500, false], // risk animation
      joker: [11000, 6000, false], // joker animation
      background: [18000, 7000, true], // background sound
      finished: [26000, 2500, false], // game finished
      countdown: [29000, 4000, false] // countdown
    }

    this.init()
    this.soundIds = {}
  }

  init () {
    console.log('init sound')
    this.howl = new Howl({
      src: [require('@/assets/lvn_milchionaer.mp3')],
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
