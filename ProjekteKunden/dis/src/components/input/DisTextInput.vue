<template>
    <v-text-field
            :value="value"
            v-bind="$attrs"
            v-on="listeners"
            :rules="getRules()"
            :label="inputLabel"
            :counter="getCounter()"
            :error-messages="fieldServerValidationErrors"
            :type="hasNumberValidator ? 'number' : 'text'"
    ></v-text-field>
</template>

<script>
import InputMixin from '../../mixins/dis-input-mixin'
export default {
  name: 'DisTextInput',
  mixins: [
    InputMixin
  ],
  props: {
    'value': {
      type: [String, Number]
    }
  },
  methods: {
    getCounter () {
      let stringValidator = this.validators.find((validation) => {
        return (validation.type === 'string')
      })
      if (stringValidator) {
        return stringValidator.max
      }
      return null
    }
  }
}
</script>

<style scoped>

</style>
