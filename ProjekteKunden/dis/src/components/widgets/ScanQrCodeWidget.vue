<template>
    <base-widget ref="baseWidget" :widget="widget" :editMode="editMode">
      <dis-qr-code-scanner></dis-qr-code-scanner>
    </base-widget>
</template>

<script>
import BaseWidget from './BaseWidget'
export default {
  name: 'ScanQrCodeWidget',
  components: { BaseWidget },
  props: {
    widget: {
      type: Object,
      required: true
    },
    editMode: {
      type: Boolean,
      required: true
    }
  },
  data () {
    return {
      isLoading: false
    }
  },
  computed: {
  },
  mounted () {
  },
  methods: {
  }
}
</script>
