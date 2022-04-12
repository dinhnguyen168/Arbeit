<template>
    <component :is="widgetType" :widget="widget" :editMode="editMode"></component>
</template>

<script>
import BaseWidget from './BaseWidget'
import ProjectInformationWidget from './ProjectInformationWidget'
import InstructionsWidget from './InstructionsWidget'
import PostBoxWidget from './PostBoxWidget'
import MessageOfTheDayWidget from './MessageOfTheDayWidget/index'
import SitesAndHolesMapWidget from './SitesAndHolesMapWidget'
import SimpleChartWidget from './SimpleChartWidget'
import ScanQrCodeWidget from './ScanQrCodeWidget'
export default {
  name: 'Widget',
  components: { BaseWidget, ProjectInformationWidget, InstructionsWidget, PostBoxWidget, MessageOfTheDayWidget, SitesAndHolesMapWidget, SimpleChartWidget, ScanQrCodeWidget },
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
  computed: {
    widgetType () {
      switch (this.widget.type) {
        case 'ProjectInformationWidget':
          return 'project-information-widget'
        case 'InstructionsWidget':
          return 'instructions-widget'
        case 'PostBoxWidget':
          return 'post-box-widget'
        case 'MessageOfTheDayWidget':
          return 'message-of-the-day-widget'
        case 'SitesAndHolesMapWidget':
          return 'sites-and-holes-map-widget'
        case 'SimpleChartWidget':
          return 'simple-chart-widget'
        case 'ScanQrCodeWidget':
          return 'scan-qr-code-widget'
        default:
          return 'base-widget'
      }
    }
  }
}
</script>
