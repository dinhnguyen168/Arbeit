<template>
    <div class="c-validators-input">
        <div>
            <v-checkbox
                    :disabled="isDbRequired || isPseudoColumn"
                    hide-details
                    label="Required"
                    v-model="validators.required.checked"/>
        </div>
        <div>
            <v-checkbox
                    disabled
                    hide-details
                    label="Number"
                    v-model="validators.number.checked"/>
            <v-text-field v-show="validators.number.checked" v-model.number="validators.number.min" placeholder="min number" />
            <v-text-field v-show="validators.number.checked" v-model.number="validators.number.max" placeholder="max number" />
        </div>
        <div>
            <v-checkbox
                    disabled
                    hide-details
                    label="String"
                    v-model="validators.string.checked"/>
            <v-text-field v-show="validators.string.checked" v-model.number="validators.string.min" placeholder="min length" />
            <v-text-field v-show="validators.string.checked" v-model.number="validators.string.max" placeholder="max length" />
        </div>
    </div>
</template>

<script>
export default {
  name: 'ValidatorsTemplate',
  props: {
    value: {
      type: Array
    },
    isDbRequired: {
      type: Boolean,
      default: false
    },
    dataModelFullName: {
      type: String,
      required: true
    },
    dataModelColumnName: {
      type: String,
      required: true
    }
  },
  data () {
    return {
      validators: {
        required: {
          checked: false
        },
        number: {
          checked: false,
          min: null,
          max: null
        },
        string: {
          checked: false,
          min: null,
          max: null
        }
      }
    }
  },
  computed: {
    dataModelTemplate () {
      return this.$store.state.templates.models.find(item => item.fullName === this.dataModelFullName)
    },
    columnDataType () {
      return this.dataModelTemplate.columns[this.dataModelColumnName].type
    },
    isPseudoColumn () {
      return this.dataModelTemplate.columns[this.dataModelColumnName].type === 'pseudo'
    }
  },
  created () {
    if (this.isDbRequired) {
      this.validators.required.checked = true
    }
  },
  methods: {
    updateValue () {
      let value = []
      for (let validator in this.validators) {
        if (this.validators[validator].checked) {
          let validatorObject = Object.assign({}, { type: validator }, this.validators[validator])
          delete validatorObject.checked
          value.push(validatorObject)
        }
      }
      this.$emit('input', value)
    }
  },
  watch: {
    validators: {
      deep: true,
      handler () {
        this.updateValue()
      }
    },
    value: {
      immediate: true,
      handler (v) {
        v.map(validator => {
          this.validators[validator.type].checked = true
          if (validator.min) {
            this.validators[validator.type].min = validator.min
          }
          if (validator.max) {
            this.validators[validator.type].max = validator.max
          }
        })
      }
    },
    columnDataType: {
      immediate: true,
      handler (v) {
        if (v === 'integer' || v === 'double') {
          this.validators.number.checked = true
          this.validators.string.checked = false
        }
        if (v === 'string' || v === 'string_multiple') {
          this.validators.number.checked = false
          this.validators.string.checked = true
        }
      }
    }
  }
}
</script>

<style scoped>
.c-validators-input input[type="text"] {
    max-width: 40px
}
</style>
