<template>
  <div id="game-wrapper" :class="{start: bStartScreen}" @click="bStartScreen = false" @touchstart.self.prevent="bStartScreen = false">
    <img id="gefaess" src="../src/assets/gefaess.png" alt="" />
    <img id="title" src="../src/assets/title.jpg" alt="" />
    <div id="question">
      <h1>
        <span>
          {{questions[counter].text}}
        </span>
      </h1>
      <ul>
        <li v-for="answer in questions[counter].answers"
            :key="answer.id" :data-answer-id="answer.id"
            @click="onAnswerClick(answer.id)"
            @touchstart.self.prevent="onAnswerClick(answer.id)"
            :class="{current: (answer.id === selectedAnswerId)}">
          <span>{{ answer.text }}</span>
        </li>
      </ul>
    </div>
    <div v-for="answer in questions[counter].answers" :key="answer.id">
      <div :class="{popup: true, show: (answer.id === selectedAnswerId)}"
           @click="selectedAnswerId = 0"
           @touchstart.self.prevent="selectedAnswerId = 0">
        <h2>{{ answer.title }}</h2>
        <p>{{ answer.paragraph }}</p>
        <button class="next-btn"
                v-if="selectedAnswerId === answer.id && answer.result && counter < 2"
                @click="nextQuestion"
        >
          >> Nächste Frage
        </button>
      </div>
    </div>
  </div>
</template>

<script>

import questions from '@/helpers/questions'

export default {
  name: 'Multiple-Choice',
  data () {
    return {
      bStartScreen: true,
      selectedAnswerId: 0,
      questions: [],
      counter: 0
    }
  },
  created () {
    this.questions = questions
  },
  methods: {
    onAnswerClick (id) {
      console.log('onAnswerClick()', id)
      this.selectedAnswerId = id
    },
    nextQuestion () {
      if (this.counter < 2) {
        this.counter += 1
      }
      this.selectedAnswerId = 0
    }
  }
}
</script>
