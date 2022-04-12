<template>
    <!--<v-switch-->
            <!--:value="value"-->
            <!--v-bind="$attrs"-->
            <!--v-on="listeners"-->
            <!--:rules="getRules()"-->
            <!--:label="inputLabel"-->
            <!--:error-messages="fieldServerValidationErrors"-->
            <!--:readonly="inputJson.readOnly"-->
    <!--&gt;</v-switch>-->
    <v-autocomplete
            :value="value"
            v-bind="$attrs"
            v-on="listeners"
            :items="items"
            item-text="text"
            item-value="value"
            :rules="getRules()"
            :label="inputLabel"
            :error-messages="fieldServerValidationErrors"
    ></v-autocomplete>
</template>

<script>
import InputMixin from '../../mixins/dis-input-mixin'
export default {
  name: 'DisSwitchInput',
  mixins: [
    InputMixin
  ],
  data () {
    return {
      items: [
        {
          text: 'yes',
          value: 1
        },
        {
          text: 'no',
          value: 0
        }
      ]
    }
  },
  props: {
    'value': {
      type: Number
    }
  }
}
</script>

<style scoped>

</style>
