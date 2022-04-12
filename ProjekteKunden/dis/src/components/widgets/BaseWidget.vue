<template>
    <v-flex :class="`xs${widget.xs_size} sm${widget.sm_size} md${widget.md_size} lg${widget.lg_size}`">
        <v-card style="display: flex; flex-direction: column;" height="100%" :dark="!!widget.is_dark" :light="!widget.is_dark" :color="widget.color">
            <v-progress-linear indeterminate v-if="isLoading" color="primary"></v-progress-linear>
            <v-card-text v-if="widget.title || widget.subtitle">
                <div v-if="widget.title" class="title">{{widget.title}}</div>
                <div v-if="widget.subtitle" class="subheading">{{widget.subtitle}}</div>
            </v-card-text>
            <v-card-text class="grow">
                <slot></slot>
            </v-card-text>
            <v-card-actions v-show="editMode">
                <v-icon class="drag-handle">drag_indicator</v-icon>
                <v-dialog v-model="settingsDialog" persistent max-width="600px">
                    <template v-slot:activator="{ on }">
                        <v-btn flat icon v-on="on">
                            <v-icon>settings</v-icon>
                        </v-btn>
                    </template>
                    <v-form  @keyup.native.enter="saveSettings" ref="settingsForm">
                        <v-card>
                            <v-card-title>
                                <span class="headline">{{widget.type}}</span>
                            </v-card-title>
                            <v-card-text>
                                <v-container grid-list-md>
                                    <v-layout wrap>
                                        <v-flex xs12 md6>
                                            <v-text-field label="Widget Title" v-model="widgetModel.title"></v-text-field>
                                        </v-flex>
                                        <v-flex xs12 md6>
                                            <v-text-field label="Widget Subtitle" v-model="widgetModel.subtitle"></v-text-field>
                                        </v-flex>
                                        <v-flex xs8 md6>
                                          <v-combobox v-model="selectedColor" :items="selectColors" :rules="[validateColor]" label="Select, enter or pick a color"></v-combobox>
                                        </v-flex>
                                        <v-flex xs4 md2>
                                          <v-input-colorpicker label="Color" class="picker" v-model="widgetModel.color" @change="onPickColor"/>
                                        </v-flex>
                                        <v-flex xs12 md4>
                                            <v-checkbox :true-value="1" :false-value="0" class="whiteText" label="White text?" v-model="widgetModel.is_dark"></v-checkbox>
                                        </v-flex>
                                        <v-flex xs12 md6>
                                            <v-select label="Size on very small screens" v-model="widgetModel.xs_size" :items="sizeOptions">
                                                <v-tooltip slot="prepend" bottom>
                                                    <v-icon slot="activator">info</v-icon>
                                                    <span>screen width &lt; 600px</span>
                                                </v-tooltip>
                                            </v-select>
                                        </v-flex>
                                        <v-flex xs12 md6>
                                            <v-select label="Size on small screens" v-model="widgetModel.sm_size" :items="sizeOptions">
                                                <v-tooltip slot="prepend" bottom>
                                                    <v-icon slot="activator">info</v-icon>
                                                    <span>600px &gt; screen width &lt; 960px</span>
                                                </v-tooltip>
                                            </v-select>
                                        </v-flex>
                                        <v-flex xs12 md6>
                                            <v-select label="Widget on medium screens" v-model="widgetModel.md_size" :items="sizeOptions">
                                                <v-tooltip slot="prepend" bottom>
                                                    <v-icon slot="activator">info</v-icon>
                                                    <span>960px &gt; screen width &lt; 1264px</span>
                                                </v-tooltip>
                                            </v-select>
                                        </v-flex>
                                        <v-flex xs12 md6>
                                            <v-select label="Size on large screens" v-model="widgetModel.lg_size" :items="sizeOptions">
                                                <v-tooltip slot="prepend" bottom>
                                                    <v-icon slot="activator">info</v-icon>
                                                    <span>screen width &gt; 1264px</span>
                                                </v-tooltip>
                                            </v-select>
                                        </v-flex>
                                    </v-layout>
                                    <slot name="extraSettingsForm" v-bind:extraSettingsFormModel="extraSettingsFormModel"></slot>
                                </v-container>
                            </v-card-text>
                            <v-card-actions>
                                <v-spacer></v-spacer>
                                <v-btn color="blue darken-1" flat @click="settingsDialog = false">Close</v-btn>
                                <v-btn color="blue darken-1" flat @click="saveSettings" :loading="isLoading">Save</v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-form>
                </v-dialog>
                <v-btn v-if="widget.cloneable" flat icon @click="duplicateWidget" :loading="isDuplicating">
                    <v-icon color="teal">file_copy</v-icon>
                </v-btn>
                <v-btn v-if="widget.deletable" flat icon @click="deleteWidget" :loading="isDeleting">
                    <v-icon color="red">delete</v-icon>
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-flex>
</template>

<script>
import colors from 'vuetify/lib/util/colors'
import InputColorPicker from 'vue-native-color-picker'
import debounce from '../../util/debounce'

export default {
  name: 'BaseWidget',
  components: {
    'v-input-colorpicker': InputColorPicker
  },
  props: {
    widget: {
      type: Object,
      required: true
    },
    editMode: {
      type: Boolean,
      required: true
    },
    extraSettingsProps: {
      type: Array,
      default: () => []
    }
  },
  data () {
    return {
      settingsDialog: false,
      selectedColor: '',
      widgetModel: {
        title: null,
        subtitle: null,
        active: null,
        color: null,
        is_dark: 0,
        xs_size: null,
        sm_size: null,
        md_size: null,
        lg_size: null
      },
      extraSettingsFormModel: {},
      isLoading: false,
      isDuplicating: false,
      isDeleting: false,
      sizeOptions: [{ text: '25%', value: 3 }, { text: '33%', value: 4 }, { text: '50%', value: 6 }, { text: '66%', value: 8 }, { text: '75%', value: 9 }, { text: '100%', value: 12 }],
      selectColors: []
    }
  },
  mounted () {
    this.widgetModel.title = this.widget.title
    this.widgetModel.subtitle = this.widget.subtitle
    this.selectedColor = this.widget.color
    this.widgetModel.is_dark = this.widget.is_dark
    this.widgetModel.active = this.widget.active
    this.widgetModel.xs_size = this.widget.xs_size
    this.widgetModel.sm_size = this.widget.sm_size
    this.widgetModel.md_size = this.widget.md_size
    this.widgetModel.lg_size = this.widget.lg_size
    for (let i = 0; i < this.extraSettingsProps.length; i++) {
      this.$set(this.extraSettingsFormModel, this.extraSettingsProps[i], null)
      this.extraSettingsFormModel[this.extraSettingsProps[i]] = this.widget.extraSettings[this.extraSettingsProps[i]]
    }
    Object.keys(colors).forEach((base) => {
      Object.keys(colors[base]).forEach((variant) => {
        this.selectColors.push(base + ' ' + variant)
      })
    })
    this.onPickColor = debounce(this.onPickColor, 300)
  },
  watch: {
    selectedColor: function (newColor) {
      console.log('watch selectedColor:', newColor)
      this.widgetModel.color = this.hexColor(newColor)
      console.log('widgetModel.color:', this.widgetModel.color)
    }
  },
  methods: {
    async saveSettings () {
      try {
        this.isLoading = true
        await this.$store.dispatch('saveWidgetSettings', { id: this.widget.id, data: Object.assign({ extraSettings: this.extraSettingsFormModel }, this.widgetModel) })
        this.settingsDialog = false
      } catch (error) {
        this.$dialog.notify.warning('could not save widget settings.')
        console.log(error)
      } finally {
        this.isLoading = false
      }
    },
    async duplicateWidget () {
      try {
        this.isDuplicating = true
        await this.$store.dispatch('duplicateWidget', { id: this.widget.id })
      } catch (error) {
        this.$dialog.notify.warning('could not duplicate widget. ' + error.message)
        console.log(error)
      } finally {
        this.isDuplicating = false
      }
    },
    async deleteWidget () {
      try {
        this.isDeleting = true
        await this.$store.dispatch('deleteWidget', { id: this.widget.id })
      } catch (error) {
        this.$dialog.notify.warning('could not delete widget. ' + error.message)
        console.log(error)
      } finally {
        this.isDeleting = false
      }
    },
    hexColor (name) {
      if (!name) return null
      if (name.startsWith('#')) return name.toLowerCase()
      const [nameFamily, nameModifier] = name.split(' ')
      const shades = ['black', 'white', 'transparent']
      const util = { family: null, modifier: null }
      if (shades.find(shade => shade === nameFamily)) {
        util.family = 'shades'
        util.modifier = nameFamily
      } else {
        const [firstWord, secondWord] = nameFamily.split('-')
        util.family = firstWord + (secondWord ? secondWord.charAt(0).toUpperCase() + secondWord.slice(1) : '')
        util.modifier = nameModifier ? nameModifier.replace('-', '') : 'base'
      }
      return colors[util.family][util.modifier]
    },
    onPickColor (value) {
      console.log('picked color', value)
      this.selectedColor = value
    },
    validateColor (value) {
      const hex = this.hexColor(value)
      if (hex && !hex.match(/^#[abcdef0123456789]{3,6}$/)) {
        return 'Please enter, select or pick a valid color'
      }
      return true
    }
  }
}
</script>

<style scoped>
  .picker {
    margin-top: 1.5em;
    width: 4em;
  }
  .whiteText {
    margin-top: 1.5em;
  }
</style>
