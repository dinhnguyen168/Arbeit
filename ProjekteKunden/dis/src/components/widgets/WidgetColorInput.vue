<template>
    <v-layout row>
        <v-flex xs-6>
            <v-select v-model="colorBase" :items="colorBaseList" label="Color Base" @input="onColorBaseInput" clearable>
                <template v-slot:item="slotProps">
                    {{slotProps.item}} <v-spacer></v-spacer> <span :class="slotProps.item">&nbsp;&nbsp;</span>
                </template>
            </v-select>
        </v-flex>
        <v-flex xs-6>
            <v-select v-model="colorVariant" :items="colorVariantList" label="Color Variant" @input="onColorVariantInput" :disabled="!colorBase" clearable>
                <template v-slot:item="slotProps">
                    {{slotProps.item}} <v-spacer></v-spacer>  <span :class="$data.colorBase + ' ' + slotProps.item">&nbsp;&nbsp;</span>
                </template>
            </v-select>
        </v-flex>
    </v-layout>
</template>

<script>
export default {
  name: 'WidgetColorInput',
  props: {
    'value': {
      type: [String, Number]
    }
  },
  data () {
    return {
      colorBase: '',
      colorVariant: '',
      colorBaseList: ['red', 'pink', 'purple', 'deep-purple', 'indigo', 'blue', 'light-blue', 'cyan', 'teal', 'green', 'light-green', 'lime', 'yellow', 'amber', 'orange', 'deep-orange', 'brown', 'blue-grey', 'grey'],
      colorVariantList: ['lighten-5', 'lighten-4', 'lighten-3', 'lighten-2', 'lighten-1', 'darken-1', 'darken-2', 'darken-3', 'darken-4', 'accent-1', 'accent-2', 'accent-3', 'accent-4']
    }
  },
  computed: {
    internalValue: {
      get () {
        return `${this.colorBase} ${this.colorVariant}`
      },
      set (value) {
        if (value && typeof value === 'string') {
          const valueParts = value.split(' ')
          valueParts[0] ? this.colorBase = valueParts[0] : this.colorBase = ''
          valueParts[1] ? this.colorVariant = valueParts[1] : this.colorVariant = ''
        }
      }
    }
  },
  methods: {
    onColorBaseInput (value) {
      if (!value) {
        this.colorVariant = ''
      }
      this.$emit('input', this.internalValue)
    },
    onColorVariantInput (event) {
      this.$emit('input', this.internalValue)
    }
  },
  watch: {
    value (value) {
      if (this.internalValue !== value) {
        // this.$emit('input', this.internalValue)
        this.internalValue = value
      }
    }
  }
}
</script>

<style scoped>

</style>
