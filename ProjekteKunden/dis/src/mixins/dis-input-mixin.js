export default {
  inheritAttrs: false,
  props: {
    name: {
      type: String,
      required: true
    },
    label: {
      type: String,
      required: true
    },
    validators: {
      type: Array,
      default: () => {
        return []
      }
    },
    serverValidationErrors: {
      type: Array
    },
    formModel: {
      type: Object
    }
  },
  methods: {
    isNotEmptyValue (v) {
      return !!v || v === 0 || v === false
    },
    getRules () {
      let rules = []
      this.validators.map(validator => {
        switch (validator.type) {
          case 'required':
            rules.push(v => this.isNotEmptyValue(v) || `${this.label} is required`)
            break
          case 'string':
            if (validator.max) {
              rules.push(v => (!v || (v && v.length <= validator.max)) || `${this.label} must be less than ${validator.max} characters`)
            }
            break
          case 'number':
            rules.push(v => {
              if (v) {
                let asNum = +v // to parse string to a number value
                let valid = !isNaN(v)
                let message = `${this.label} must be a number`
                if (valid && validator.min && !isNaN(validator.min)) {
                  valid = (asNum >= validator.min)
                  message = `${this.label} must be >= ${validator.min}`
                }
                if (valid && validator.max && !isNaN(validator.max)) {
                  valid = (asNum <= validator.max)
                  message = `${this.label} must be <= ${validator.max}`
                }
                return valid || message
              } else {
                return true
              }
            })
            break
          case 'unique':
            break
          default:
            throw new Error(`Dis: Unsupported validation type '${validator.type}'`)
        }
      })
      return rules
    }
  },
  computed: {
    hasNumberValidator () {
      return this.validators.findIndex(element => element.type === 'number') > -1
    },
    fieldServerValidationErrors () {
      let errors = []
      this.serverValidationErrors.map(error => {
        if (error.field === this.name) {
          errors.push(error.message)
        }
      })
      return errors
    },
    listeners () {
      return {
        ...this.$listeners
        // add more listeners here if needed
      }
    },
    isRequired () {
      return this.validators.findIndex(item => item.type === 'required') > -1
    },
    inputLabel () {
      return this.isRequired ? `*${this.label}` : this.label
    }
  }
}
