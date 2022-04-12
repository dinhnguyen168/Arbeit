<template>
  <div class="c-datetime-input">
    <input type="text" class="picker-input" ref="pickerInput" v-model="value" v-on="listeners">
    <v-text-field
            data-input="true"
            v-bind="$attrs"
            :rules="getRules()"
            :label="inputLabel"
            :error-messages="fieldServerValidationErrors"
            @focus="onFocus"
            append-outer-icon="schedule"
            @click:append-outer="onOuterIconClick"
    ></v-text-field>
  </div>
</template>

<script>
import InputMixin from '../../mixins/dis-input-mixin'
import Flatpickr from 'flatpickr'
// import DateTimeInput from './DateTimeInput'
export default {
  name: 'DisDateTimeInput',
  components: {
  },
  mixins: [
    InputMixin
  ],
  props: {
    'value': {
      default: null,
      required: true,
      validator (value) {
        return value === null || value instanceof Date || typeof value === 'string' || value instanceof String || value instanceof Array || typeof value === 'number'
      }
    }
  },
  data () {
    return {
      displayValue: null
    }
  },
  mounted () {
    // Return early if flatpickr is already loaded
    if (this.fp) return
    // Don't mutate original object on parent component
    const config = {
      wrap: true,
      enableTime: true,
      // dateFormat: 'Z',
      dateFormat: 'Y-m-d H:i',
      time_24hr: true,
      defaultDate: this.value || null,
      onOpen: this.onCalendarOpen,
      parseDate: this.parseDate,
      clickOpens: false,
      onValueUpdate: this.onPickerValueUpdate,
      disableMobile: true
    }
    // Init flatpickr
    this.fp = new Flatpickr(this.$el, config)
    // Immediate watch will fail before fp is set,
    // so need to start watching after mount
    this.$watch('disabled', this.watchDisabled, { immediate: true })
  },
  methods: {
    fpInput () {
      return this.fp.altInput || this.fp.input
    },
    watchDisabled (newState) {
      if (newState) {
        this.fpInput().setAttribute('disabled', newState)
      } else {
        this.fpInput().removeAttribute('disabled')
      }
    },
    onCalendarOpen () {
      if (this.$attrs.readonly || this.$attrs.disabled) {
        this.fp.close()
      }
    },
    parseDate (dateString, format) {
      if (dateString) {
        return this.$dateTime(dateString).dateObject
      }
      return null
    },
    formatDate (date, format) {
      return this.$dateTime(date).formatForInput()
    },
    onFocus () {
      !this.fp.isOpen && this.fp.open()
    },
    onPickerValueUpdate (value) {
      this.$emit('input', this.$dateTime(value).toUtc().formatForDB())
    },
    onOuterIconClick () {
      !this.$attrs.disabled && !this.$attrs.readonly && this.$emit('input', this.$dateTime(new Date()).toUtc().formatForDB())
    }
  },
  watch: {
    value (newValue) {
      // newValue is always in UTC
      if (!newValue && this.fp) {
        this.fp.setDate(null, true)
        return
      }
      // Prevent updates if v-model value is same as input's current value
      const inputValue = this.fpInput().value ? this.$dateTime(this.fpInput().value).toUtc().formatForDisplay() : this.fpInput().value
      if (newValue === inputValue) return
      if (newValue && this.fp) {
        const dateObject = this.$dateTime(newValue).dateObject
        this.fp && this.fp.setDate(this.$dateTime(dateObject).asUtc().formatForInput(), true)
      }
    }
  },
  beforeDestroy () {
    if (this.fp) {
      this.fp.destroy()
      this.fp = null
    }
  }
}
</script>

<style scoped>
@import '~flatpickr/dist/flatpickr.min.css';
@import '~flatpickr/dist/themes/dark.css';
.c-datetime-input {
  position: relative;
}
.picker-input {
  display: none;
}
</style>
