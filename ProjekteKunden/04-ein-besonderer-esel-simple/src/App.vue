<template>
  <div id="game-wrapper" :class="{start: bStartScreen}" @click="bStartScreen = false" @touchstart.self.prevent="bStartScreen = false">
    <img id="donkey" src="../src/assets/esel.png" alt="" />
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
        text: 'Warum ist Jesus auf einem Esel nach Jerusalem geritten?',
        answers: [
          {
            id: 1,
            text: 'Er hat den Zug verpasst!',
            popup: '<h2>Leider falsch.</h2> <p>Naja, in der Zeit, als Jesus gelebt hat, gab es noch keine Züge. Dieser Zug ist abgefahren – versuch es noch einmal!</p>'
          },
          {
            id: 2,
            text: 'Sein edles Pferd hatte sich verletzt.',
            popup: '<h2>Leider falsch.</h2> <p>Ein edles Reitpferd konnten sich damals nur reiche Menschen leisten. Und Jesus war arm. Das Pferd bringt dich leider nicht weiter – versuch es noch einmal!</p>'
          },
          {
            id: 3,
            text: 'Der Esel war ein Lastentier der einfachen Leute.',
            popup: '<h2>Richtig!</h2> <p>Der Esel mit seinem eher unscheinbaren grauen Fell, <br>seiner geringen Körperhöhe und seiner manchmal unberechenbaren Art wurde schon immer verspottet und als Schimpfwort für dumme Menschen verwendet. <br>Jesus wollte zeigen, dass er kein stolzer, reicher Held ist, sondern ein Mensch wie jeder, der einfach und bescheiden ist.</p>'
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
