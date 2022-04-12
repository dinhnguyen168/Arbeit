<template>
    <v-text-field
            :value="value"
            v-bind="$attrs"
            v-on="listeners"
            :rules="getRules()"
            :label="inputLabel"
            :error-messages="fieldServerValidationErrors"
            type="time"
    ></v-text-field>
</template>

<script>
import InputMixin from '../../mixins/dis-input-mixin'
export default {
  name: 'DisTimeInput',
  mixins: [
    InputMixin
  ],
  props: {
    'value': {
      type: String
    }
  },
  data () {
    return {}
  },
  methods: {
  }
}
</script>

<style scoped>

</style>
