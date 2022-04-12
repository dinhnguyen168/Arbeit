import { Howl } from 'howler'

window.START_TIME = 1.5 // in Sekunden

class Sound {
    constructor () {
        this.sprites = {
            start: [0, window.START_TIME * 1000, false], // spielstart
            background: [18000, 7000, true], // background sound
            grab: [4000, 1500, false], // label grab
            drop: [6000, 350, false], // label drop
            correct: [0, 1500, false], // scheibe drehen
            incorrect: [2000, 1500, false], // label springen zurück
            finished: [26000, 2500, false], // spielende
        }

        this.init()
        this.soundIds = {}
    }

    init () {
        console.log('init sound')
        this.howl = new Howl({
            src: [require('@/assets/sound/symphony.mp3')],
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
