
class Question {
  constructor (question, points, category) {
    this.category = category
    this.question = question
    this.points = points
    this.answers = []
    this.done = false
    this.answeredByPlayer = 0
    this.timeouts = 0
    this.disableInput = false
  }

  get countAnswered () {
    return (this.answers.filter(answer => answer.answered).length)
  }

  get isAnswered () {
    return this.countAnswered > 0
  }

  get type () {
    return window.QUESTION_TYPE_NORMAL
  }

  addAnswer (answer) {
    this.answers.push(answer)
    this.answers.shuffle()
  }

  get cssClasses () {
    const classes = ['question', 'q' + this.points, 'type_' + this.type]
    if (this.done) {
      classes.push('done')
      if (this.answeredByPlayer > 0) {
        classes.push('answered')
        classes.push('answeredBy' + this.answeredByPlayer)
      } else {
        classes.push('failed')
      }
    }
    return classes.join(' ')
  }

  getPoints (correct) {
    return correct ? this.points : 0
  }

  select (app) {
    app.showQuestion(this)
  }

  onTimeout (app) {
    this.timeouts++
    app.sound.play('incorrect')
    if (this.countAnswered < 1 && this.timeouts === 1 && app.players.length > 1) {
      app.nextPlayer()
    } else {
      this.onSecondTimeout(app)
    }
  }

  onSecondTimeout (app) {
    app.closeQuestion(this)
  }

  selectAnswer (answer, app) {
    answer.answered = true
    console.log('question.selectAnswer() correct:', answer.correct, '; count:', this.countAnswered)
    if (answer.correct || this.countAnswered > 1 || this.timeouts > 0 || app.players.length === 1) {
      this.disableInput = true
      app.setQuestionAnswered(answer)
      app.closeQuestion(this)
    } else {
      app.nextPlayer()
    }
  }
}

class RiskQuestion extends Question {
  constructor (question, points, category) {
    super(question, points, category)
    this.boardFields = []
  }

  get type () {
    return window.QUESTION_TYPE_RISK
  }

  getPoints (correct) {
    return correct ? this.points * 2 : -this.points
  }

  select (app) {
    setTimeout(() => { this.animateRisk(app, 0) }, (window.RISK_ANIMATION_DURATION * 1000) / 50)
    setTimeout(() => { app.sound.play('risk') }, 100)
    window.setTimeout(() => {
      app.showRiskAnimation = false
      this.showAfterAnimationFinished(app)
    }, window.RISK_ANIMATION_DURATION * 1000)
  }

  animateRisk (app, num) {
    if (num === 0) {
      this.boardFields = []
      const board = document.getElementById('board')
      for (var n = 0; n < board.childNodes.length; n++) {
        var cat = board.childNodes[n]
        for (var i = 0; i < cat.childNodes.length; i++) {
          this.boardFields.push(cat.childNodes[i])
        }
      }
      this.num = -1
    }
    app.$nextTick(() => {
      app.showRiskAnimation = true
    })

    if (num < this.boardFields.length) {
      this.boardFields[num].classList.add('risk-color')
      setTimeout(() => {
        this.animateRisk(app, num + 1)
      }, (window.RISK_ANIMATION_DURATION * 1000) / 50)
      setTimeout(() => { this.boardFields[num].classList.remove('risk-color') }, (window.RISK_ANIMATION_DURATION * 1000) / 2.5)
    }
  }

  showAfterAnimationFinished (app) {
    app.showQuestion(this)
  }

  onTimeout (app) {
    app.closeQuestion(this)
  }

  selectAnswer (answer, app) {
    this.disableInput = true
    answer.answered = true
    app.setQuestionAnswered(answer)
    app.closeQuestion(this)
  }
}

class JokerQuestion extends Question {
  get type () {
    return window.QUESTION_TYPE_JOKER
  }

  getPoints (correct) {
    return this.points
  }

  select (app) {
    setTimeout(() => { this.animateJoker(app, 0) }, (window.JOKER_ANIMATION_DURATION * 1000) / 50)
    this.disableInput = true
    // app.showJokerAnimation = true
    setTimeout(() => { app.sound.play('joker') }, 100)
    const correctAnswer = this.answers.filter(ans => ans.correct)[0]
    correctAnswer.answered = true
    setTimeout(() => {
      app.showJokerAnimation = false
      this.done = true
      app.setQuestionAnswered(correctAnswer, this)
      app.hideQuestion()
    }, window.JOKER_ANIMATION_DURATION * 1000)
  }

  animateJoker (app, num) {
    if (num === 0) {
      this.boardFields = []
      const board = document.getElementById('board')
      for (var n = 0; n < board.childNodes.length; n++) {
        var cat = board.childNodes[n]
        for (var i = 0; i < cat.childNodes.length; i++) {
          this.boardFields.push(cat.childNodes[i])
        }
      }
      this.num = -1
    }
    app.$nextTick(() => {
      app.showJokerAnimation = true
    })

    if (num < this.boardFields.length) {
      this.boardFields[num].classList.add('joker-color')
      setTimeout(() => {
        this.animateJoker(app, num + 1)
      }, (window.JOKER_ANIMATION_DURATION * 1000) / 50)
      setTimeout(() => { this.boardFields[num].classList.remove('joker-color') }, (window.JOKER_ANIMATION_DURATION * 1000) / 2.5)
    }
  }
}

export { Question, RiskQuestion, JokerQuestion }
