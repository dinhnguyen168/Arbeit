<template>
  <div class="c-length">
    <div class="c-length__rectitle">Core recovery: {{coreRecovery}} m</div>
    <div class="c-length__recovery" :style="`width: 90%`" />
    <div class="c-length__cumulative" :style="`width: ${ coreRecovery ? reactiveCumulativeSectionLength / coreRecovery * 80 : 0}%`" />
    <div class="c-length__cumtitle">Cumulative length of sections: {{reactiveCumulativeSectionLength}} m</div>
  </div>
</template>

<script>
import CrudService from '../../services/CrudService.js'
export default {
  name: 'CumulativeSectionLengthIndicator',
  props: {
    coreId: {
      type: Number,
      required: true
    },
    sectionId: {
      type: Number
    },
    currentSectionLength: {
      type: Number
    }
  },
  data () {
    return {
      cumulativeSectionLength: 0,
      coreRecovery: 0
    }
  },
  computed: {
    reactiveCumulativeSectionLength () {
      return this.cumulativeSectionLength + this.currentSectionLength
    }
  },
  methods: {
    async fetchCoreSectionInformation () {
      const coreService = new CrudService('CoreCore')
      const coreModel = await coreService.get(this.coreId)
      this.coreRecovery = coreModel.core_recovery
      const sectionService = new CrudService('CoreSection')
      const sectionsModel = await sectionService.getList({ 'fields': 'id,init_length' }, { 'core_id': this.coreId })
      this.cumulativeSectionLength = 0
      let sections = sectionsModel.items
      if (this.sectionId) {
        sections = sections.filter(item => item.id !== this.sectionId)
      }
      for (const section of sections) {
        this.cumulativeSectionLength += section.init_length
      }
    }
  },
  watch: {
    'coreId': {
      immediate: true,
      handler () {
        this.fetchCoreSectionInformation()
      }
    }
  }
}
</script>

<style lang="scss" scoped >
.c-length {
  > div {
    padding: 4px;
  }

  &__recovery {
    height: 5px;
    background-color: red;
  }

  &__cumulative {
    height: 5px;
    background-color: cyan;
    transition: width .5s linear;
    max-width: 105%
  }

  &__rectitle {
    color: red;
    text-align: center;
    width: 100%
  }

    &__cumtitle {
    color: cyan;
    text-align: center;
    width: 100%
  }
}
</style>
