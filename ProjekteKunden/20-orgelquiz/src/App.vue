<template>
  <div id="game-wrapper" :class="{start: bStartScreen}" @click="bStartScreen = false" @touchstart.self.prevent="bStartScreen = false">
    <div id="object">
      <img src="../src/assets/orgel.png" alt="" />
      <!-- p><span>© bpk / Scala</span></p -->
      </div>
    <img id="title" src="../src/assets/title.jpg" alt="" />
    <div id="question">
      <h1>
        <span>
          {{question.text}}
        </span>
      </h1>
      <ul>
        <li v-for="answer in question.answers" :key="answer.id" :data-answer-id="answer.id" @click="onAnswerClick(answer.id)" @touchstart.self.prevent="onAnswerClick(answer.id)" :class="{current: (answer.id == selectedAnswerId)}">
          <span>{{ answer.text }}</span>
        </li>
      </ul>
    </div>
    <div>
      <div :class="{popup: true, show: (answer.id == selectedAnswerId)}" v-for="answer in question.answers" :key="answer.id" @click="selectedAnswerId = 0" @touchstart.self.prevent="selectedAnswerId = 0" v-html="answer.popup"></div>
    </div>
  </div>
</template>

<script>

export default {
  name: 'Multiple-Choice',
  data () {
    return {
      bStartScreen: true,
      selectedAnswerId: 0,
      question: {
        text: 'Wie hoch ist die Orgel?',
        answers: [
          {
            id: 1,
            text: '8 Meter',
            popup: '<h2>Leider falsch.</h2>'
          },
          {
            id: 2,
            text: '10 Meter',
            popup: '<h2>Leider falsch.</h2>'
          },
          {
            id: 3,
            text: '14 Meter',
            popup: '<h2>Richtig. Super!</h2> <img src="img/Orgel_Masse.jpg" alt="Die Orgel ist 14 Meter hoch" >'
          }
        ]
      }
    }
  },
  created () {
  },
  methods: {
    onAnswerClick (id) {
      console.log('onAnswerClick()', id)
      this.selectedAnswerId = id
    }
  }
}
</script>
