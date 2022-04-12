<template>
  <v-alert v-model="showErrorSummary" dismissible>
    <ul>
      <li v-for="error in value" :key="error.field">
        <strong>{{error.field}}</strong>: {{error.message}}
      </li>
    </ul>
  </v-alert>
</template>

<script>
export default {
  name: 'DisServerValidationErrorsView',
  props: {
    value: {
      type: Array
    }
  },
  computed: {
    showErrorSummary: {
      get: function () {
        return this.value.length > 0
      },
      set: function (v) {
        !v && this.$emit('input', [])
      }
    }
  }
}
</script>
