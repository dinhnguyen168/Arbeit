<template>
  <div id="gameWrapper" @mousedown="touchstart($event)" @mouseup="touchend()" @touchstart="touchstart($event)" @touchend="touchend()" >
    <div id="startScreen" v-if="!started">
      <h1 class="name">Wer wird Milchionär!</h1>
      <div class="menuContainer">
        <h2>Fragendatei auswählen:</h2>
        <select class="questionFile" v-model="questionFile">
          <option v-for="item in questionItems" :key="item.id" v-bind:value="item.name">{{item.name}}</option>
        </select>

        <h2>Spieler</h2>
        <div class="nameField">
          <input type="text" placeholder="Spieler 1" v-model="firstPlayer" @change="addPlayer(firstPlayer, 1)">
          <input type="text" placeholder="Spieler 2" v-model="secondPlayer" @change="addPlayer(secondPlayer, 2)" :disabled="!firstPlayer" >
        </div>
        <button id="setting" @click="showSettings = !showSettings">Einstellungen</button>
        <div id="settingDialog" v-if="showSettings">
          <div id="settingContainer">
            <h2 id="settingTitle">Einstellungen</h2>
            <span class="x" @click="showSettings = !showSettings">x</span>
            <div class="range-slider" v-for="(setting, id) in settings" :key="id">
              <h2 class="range-slider__title">{{ setting.name }}</h2>
              <input class="range-slider__range" type="range"
                     v-model="setting.value"
                     :min="setting.min"
                     :max="setting.max"
                     :step="setting.step">
              <span class="range-slider__value">{{setting.value}}</span>
            </div>
            <button id="settingOKButton" @click="showSettings = !showSettings">Ok</button>
          </div>
        </div>
        <button id="startButton" :disabled="!firstPlayer || !questionFile" @click="startGame">Los geht's!</button>
      </div>
    </div>
    <div id="playScreen" v-else>
      <div id="board" :class="{ activeQuestion: activeQuestion, riskAnimation: showRiskAnimation }">
        <div class="category" v-for="(categoryQuestions, category) in board" :key="category" >
          <div :class="{ title: true, activeQuestion: activeQuestion && activeQuestion.category == category }">{{ category }}</div>
          <div v-for="question in categoryQuestions" :key="question.points" :class="question.cssClasses" @click="selectQuestion(question)">
            <span>{{ question.points }}</span>
          </div>
        </div>
      </div>
      <div id="players">
        <div v-for="player in players" :key="player.id" :class="{player: true, active: activePlayer && player.id == activePlayer.id, leading: isLeading(player)}" @click="activePlayer = player">
          <div class="name">{{ player.name }}</div>
          <div class="score">{{ player.score }}</div>
        </div>
      </div>
      <div id="question" v-if="activeQuestion && !activeQuestion.done">
        <div class="title">{{ activeQuestion.question }}</div>
        <ol>
          <li v-for="answer in activeQuestion.answers" :key="answer.text" @click="selectAnswer(answer, activeQuestion)" :class="{ answer: true, answered: answer.answered, success: answer.answered && answer.correct, failed: answer.answered && !answer.correct }">
            <span>{{ answer.text }}</span>
          </li>
        </ol>
      </div>
      <div id="result" v-if="finished">
        <ol>
          <li v-for="player in [...players].sort((a, b) => b.score - a.score)" :key="player.id">
            Platz: {{ player.name }} mit {{ player.score }} Punkten
          </li>
        </ol>
        <h4 class="reset" @click="resetGame">Spiel noch einmal!</h4>
      </div>
      <timer
        :sound="sound"
        @timeout="onTimeout"
        ref="timer"
      ></timer>
      <div class="joker">
        <div v-if="showJokerAnimation" class="joker-animation">Joker!</div>
      </div>
      <div class="risk">
        <div v-if="showRiskAnimation" class="risk-animation">RISIKO!</div>
      </div>
    </div>
  </div>
</template>

<script>
import Timer from '@/components/Timer'
import { settings } from './utils/setting'
import { Sound } from '@/utils/sound'
import { Question, RiskQuestion, JokerQuestion } from '@/utils/question'

export default {
  name: 'Milchionaer',
  components: {
    Timer
  },
  data () {
    return {
      lastQuizData: null,
      board: {},
      preloadImageUrls: [],
      players: [], // Spieler (einer oder zwei)
      activePlayer: null, // Spieler der dran ist
      activeQuestion: null, // Frage die angezeigt wird
      playerTurnedInQuestion: false, // Während der Frage wurde der Spieler gewechselt
      showJokerAnimation: false, // Joker-Animation wird angezeigt
      showRiskAnimation: false, // Risiko-Animation wird angezeigt
      finished: false, // Das Spiel ist zu Ende
      started: false,
      questionFile: null,
      questionItems: [],
      firstPlayer: null,
      secondPlayer: null,
      showSettings: false,
      settings: {},
      switchFullscreen: true
    }
  },
  created () {
    window.QUESTION_TYPE_NORMAL = 'normal'
    window.QUESTION_TYPE_JOKER = 'joker'
    window.QUESTION_TYPE_RISK = 'risk'
    window.QUESTION_CLOSE_DELAY = 1.8 // seconds
    window.JOKER_ANIMATION_DURATION = 6 // seconds
    window.RISK_ANIMATION_DURATION = 4.5 // seconds
    this.settings = settings

    this.sound = new Sound()

    const file = 'config.json'
    console.log('fetch config file ', file)
    fetch(file)
      .then(response => response.json())
      .then((data) => {
        window.quizDateien = data
        this.questionItems = Object.keys(data).map((item, index) => ({
          id: index,
          name: item
        }))
      })
  },
  mounted () {
    this.delayPreloadImages()
    if (this.switchFullscreen) {
      const onClickFullscreen = () => {
        const elem = document.documentElement
        console.log('onClickFullscreen', elem)
        if (elem.requestFullscreen) {
          elem.requestFullscreen()
        } else if (elem.webkitRequestFullscreen) { /* Safari */
          elem.webkitRequestFullscreen()
        } else if (elem.msRequestFullscreen) { /* IE11 */
          elem.msRequestFullscreen()
        }
        window.removeEventListener('click', onClickFullscreen)
      }
      window.addEventListener('click', onClickFullscreen)
    }
  },
  watch: {
    // check Veränderung der Bedenkzeit
    showSettings (show) {
      if (!show) {
        for (var id in this.settings) {
          this.settings[id].value = parseInt(this.settings[id].value)
        }
      }
    }
  },
  methods: {
    loadJSFile () {
      const file = window.quizDateien[this.questionFile]
      console.log('fetch file ', file)
      fetch(file)
        .then(response => response.text())
        .then((data) => {
          const quiz = JSON.parse(data)
          this.createGame(quiz)
        })
    },
    // Erzeugt ein Spielbrett
    createGame (quiz) {
      if (!quiz) quiz = this.lastQuizData
      else this.lastQuizData = quiz
      console.log('createGame()', quiz)
      let categories = Object.keys(quiz)
      categories.shuffle()
      categories = categories.slice(0, Math.min(this.settings.numberCategories.value, categories.length))

      this.board = {}

      let cntJokers = 0
      let cntRisk = 0
      // Schleife durch alle Kategorien
      for (let i = 0; i < categories.length; i++) {
        var kategorie = categories[i]
        var kategorieData = quiz[kategorie]

        var pointsQuestions = { 25: [], 50: [], 75: [], 100: [] }

        // Schleife durch alle Fragen der aktuellen Kategorie
        for (var frage in kategorieData) {
          var frageData = kategorieData[frage]
          var question
          switch (this.randomQuestionType()) {
            case window.QUESTION_TYPE_JOKER:
              question = new JokerQuestion(frageData.frage, frageData.punkte, kategorie)
              break
            case window.QUESTION_TYPE_RISK:
              question = new RiskQuestion(frageData.frage, frageData.punkte, kategorie)
              break
            default:
              question = new Question(frageData.frage, frageData.punkte, kategorie)
          }

          // Schleife durch alle Antworten der aktuellen Frage
          for (var indexAntwort in frageData.antworten) {
            var text = frageData.antworten[indexAntwort]
            var antwort = { text: text, correct: (frageData.richtig === parseInt(indexAntwort) + 1), answered: false }
            question.addAnswer(antwort)
          }

          // TODO: In Punkte außer der konktreten Punktzahl auch Wildcard oder mehrere Punkte (z.B. 50 und 75) zulassen.
          pointsQuestions[frageData.punkte].push(question)
        }

        for (const points in pointsQuestions) {
          pointsQuestions[points].shuffle()
          pointsQuestions[points] = pointsQuestions[points][0]
          if (pointsQuestions[points].type === window.QUESTION_TYPE_JOKER) cntJokers++
          if (pointsQuestions[points].type === window.QUESTION_TYPE_RISK) cntRisk++
        }

        this.board[kategorie] = pointsQuestions
      }

      const cntQuestions = categories.length * 4
      console.log('board', this.board, 'Jokers: ', cntJokers, '(', Math.round(cntJokers * 100.0 / cntQuestions), '%); Risks:', cntRisk, '(', Math.round(cntRisk * 100.0 / cntQuestions), '%)')
    },
    // Frage wurde ausgewählt
    selectQuestion (question) {
      if (this.showJokerAnimation || this.showRiskAnimation || question.done || (this.activeQuestion && !this.activeQuestion.done)) return
      this.playerTurnedInQuestion = false
      this.sound.play('changePlayer')
      question.select(this)
    },
    // Frage wird angezeigt
    showQuestion (question) {
      this.playerTurnedInQuestion = false
      this.activeQuestion = question
      this.$refs.timer.startTimer(this.settings.questionTimeout.value, 100)
    },
    // Der Timer für die aktuelle Frage ist abgelaufen
    onTimeout () {
      console.log('app.onTimeout')
      if (this.activeQuestion && !this.activeQuestion.done) {
        this.activeQuestion.onTimeout(this)
      }
    },
    // Eine Antwort wurde ausgewählt
    selectAnswer (answer) {
      if (this.activeQuestion && !this.activeQuestion.disableInput && !answer.answered) {
        if (answer.correct) this.sound.play('correct')
        else this.sound.play('incorrect')
        this.activeQuestion.selectAnswer(answer, this)
      }
    },
    // Die Antowort wird als beantwortet gekennzeichnet, falls richtig werden Punkte vergeben
    setQuestionAnswered (answer, question) {
      this.$refs.timer.pauseTimer()
      if (this.activeQuestion) question = this.activeQuestion
      answer.answered = true
      this.activePlayer.score = Math.max(0, this.activePlayer.score + question.getPoints(answer.correct))
      if (answer.correct) {
        question.answeredByPlayer = this.activePlayer.id
      }
    },
    // Die aktuelle Frage soll geschlossen werden
    closeQuestion (question) {
      if (this.activeQuestion && this.activeQuestion === question) {
        this.activeQuestion.disableInput = true
        setTimeout(() => {
          this.hideQuestion()
        }, 1000)
      }
    },
    // Die Frage wird geschlossen
    hideQuestion () {
      if (this.activeQuestion) {
        this.activeQuestion.done = true
        this.activeQuestion = null
      }
      this.$refs.timer.stopTimer()
      if (!this.playerTurnedInQuestion) this.nextPlayer()
      this.updateFinished()
    },
    // Der Spieler wird gewechselt (entweder während einer Frage oder nach einer Frage)
    nextPlayer () {
      if (this.players.length > 1) {
        this.activePlayer = this.players.filter(player => player.id !== this.activePlayer.id)[0]
        console.log('nextPlayer() activePlayer:', this.activePlayer)
        if (this.activeQuestion) {
          this.playerTurnedInQuestion = true
          let percent = 100 - this.$refs.timer.getPercent()
          console.log('getPercent:', percent)
          percent = Math.max(50, percent)
          this.$refs.timer.startTimer(this.settings.questionTimeout.value * percent / 100, percent)
        }
      }
    },
    // Führt der genannte Spieler?
    isLeading (player) {
      return this.players.filter(p => p.score > player.score).length === 0
    },
    // Liefert einen zufälligen Fragetypen abhängig der eingestellten Wahrscheinlichkeiten
    randomQuestionType () {
      const percentage = Math.floor(Math.random() * 100)
      if (percentage <= this.settings.jokerPercentage.value) {
        return window.QUESTION_TYPE_JOKER
      } else if (percentage <= this.settings.jokerPercentage.value + this.settings.riskPercentage.value) {
        return window.QUESTION_TYPE_RISK
      } else {
        return window.QUESTION_TYPE_NORMAL
      }
    },
    // Erzeugt ein neues Spielbrett und setzt das Spiel zurück
    resetGame () {
      this.sound.stop()
      this.started = false
      this.createGame()
      this.firstPlayer = null
      this.secondPlayer = null
      this.createGame()
      this.players = []
      this.updateFinished()
    },
    // Überprüft, ob das Spielbrett zu Ende gespielt wurde
    updateFinished () {
      const foundNotDone = Object.values(this.board).some((questions) => {
        return Object.values(questions).some(question => !question.done)
      })
      this.finished = !foundNotDone
      if (this.finished) {
        this.sound.play('finished')
      }
    },
    delayPreloadImages () {
      const imgs = document.querySelectorAll('img')
      for (let i = 0; i < imgs.length; ++i) {
        const img = imgs[i]
        if (!img.complete) {
          console.log('deplayPreloadImage() waiting for ', img)
          setTimeout(() => { this.delayPreloadImages() }, 1000)
          return
        }
      }
      this.preloadImages()
    },
    preloadImages () {
      if (this.preloadImageUrls.length > 0) {
        const url = this.preloadImageUrls.shift()
        const img = new Image()
        img.onload = () => {
          this.preloadImages()
        }
        console.log('preload image ', url)
        img.src = url
      }
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
      // event.preventDefault()
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
    },
    addPlayer (player, index) {
      if (player) {
        this.players.push({
          id: index,
          name: player,
          score: 0
        })
      }
    },
    startGame () {
      this.loadJSFile()
      this.activePlayer = this.players[0]
      this.started = true
      // this.sound.play('background')
    }
  }
}
</script>
