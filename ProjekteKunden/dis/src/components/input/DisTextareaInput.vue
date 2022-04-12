<template>
    <v-textarea
            :value="value"
            v-bind="$attrs"
            v-on="listeners"
            :rules="getRules()"
            :label="inputLabel"
            :error-messages="fieldServerValidationErrors"
    ></v-textarea>
</template>

<script>
import InputMixin from '../../mixins/dis-input-mixin'
export default {
  name: 'DisTextareaInput',
  mixins: [
    InputMixin
  ],
  props: {
    'value': {
      type: String
    }
  }
}
</script>

<style scoped>

</style>
