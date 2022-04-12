<template>
    <v-text-field
            :value="inputValue"
            v-bind="$attrs"
            v-on="listeners"
            :rules="getRules()"
            :label="inputLabel"
            :error-messages="fieldServerValidationErrors"
            type="date"
            :append-outer-icon="'alarm_add'"
            @click:append-outer="onOuterIconClick"
    ></v-text-field>
</template>

<script>
import InputMixin from '../../mixins/dis-input-mixin'
export default {
  name: 'DisDateInput',
  mixins: [
    InputMixin
  ],
  props: {
    'value': {
      type: String
    }
  },
  data () {
    return {
      inputValue: ''
    }
  },
  watch: {
    value (newValue) {
      // console.log("check : " , newValue, typeof(newValue))
      this.inputValue = newValue
    }
  },
  methods: {
    onOuterIconClick () {
      let currentDatetime = new Date().toLocaleDateString('en-GB').split('/').reverse().join('-')
      !this.$attrs.disabled && !this.$attrs.readonly && this.$emit('input', currentDatetime)
    }
  }
}
</script>

<style scoped>

</style>
