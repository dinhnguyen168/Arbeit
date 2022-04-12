<template>
    <v-menu v-model="popover" :close-on-content-click="false" offset-y >
        <template #activator="data">
            <v-btn v-on="data.on" flat small color="blue">
                details
            </v-btn>
        </template>
        <v-card>
            <v-card-text>
                <div class="c-generated-files">
                    <div>
                        {{sharedStartPath}}
                        <ul class="c-generated-files__list">
                            <li v-for="(file, index) in files" :key="index">
                                <v-tooltip top v-if="file.modified">
                                    <span slot="activator">
                                        <span class="c-generated-files__file">
                                            {{file.path.substring(sharedStartPath.length)}}
                                            <span class="c-generated-files__file--old" v-if="templateGeneratedAt > file.modified"></span>
                                        </span>
                                    </span>
                                    <span>{{file.modified * 1000 | formatTimestamp}}</span>
                                </v-tooltip>
                                <span v-else class="c-generated-files__file">{{file.path.substring(sharedStartPath.length)}}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </v-card-text>
        </v-card>
    </v-menu>
</template>

<script>
export default {
  name: 'GeneratedFiles',
  props: {
    templateGeneratedAt: {
      type: Number,
      required: true
    },
    files: {
      type: Array,
      required: true
    }
  },
  data () {
    return {
      popover: false
    }
  },
  computed: {
    sharedStartPath () {
      let files = JSON.parse(JSON.stringify(this.files.map(item => item.path)))
      files = files.sort()
      let first = files[0]
      let last = files[files.length - 1]
      let length = first.length
      let i = 0
      while (i < length && first.charAt(i) === last.charAt(i)) {
        i++
      }
      let shared = first.substring(0, i)
      i = shared.lastIndexOf('/')
      if (i > 1) {
        shared = shared.substring(0, i)
      }
      return shared
    }
  }
}
</script>

<style lang="stylus">
    .c-generated-files
        &__list
            list-style none
            margin 0 0 0 1rem
            padding 0
        &__file
            &--old
                width 5px
                height 5px
                margin-bottom 8px
                display inline-block
                background-color red
                border-radius 50%
</style>
