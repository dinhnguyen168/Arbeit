<template>
    <div :class="Object.assign({'c-column-template': true}, themeClasses)">
        <dis-simple-panel :newColumnAdded="value.newColumnAdded">
            <template v-slot:head>
                <v-icon class="drag-handle mr-3">drag_indicator</v-icon>
                <h3>
                  {{columnName}}
                </h3>
                <v-tooltip right>
                  <template v-slot:activator="{ on, attrs }">
                    <v-icon v-if="value.isLocked || value.name === 'id'"
                            class="ml-2"
                            color="grey darken-1"
                            v-bind="attrs"
                            v-on="on"
                    >lock_outline</v-icon>
                    <v-icon v-if="value.type === 'one_to_many' || value.type === 'many_to_many'"
                            class="ml-2"
                            color="grey darken-1"
                            v-bind="attrs"
                            v-on="on"
                    >all_inclusive</v-icon>
                    <v-icon v-if="value.name === parentModelColumnName"
                            class="ml-2"
                            color="grey darken-1"
                            v-bind="attrs"
                            v-on="on"
                    >scatter_plot</v-icon>
                  </template>
                  <span v-if="value.isLocked || value.name === 'id'">Can not be modified</span>
                  <span v-if="value.type === 'one_to_many' || value.type === 'many_to_many'">
                    Part of a relation
                  </span>
                  <span v-if="value.name === parentModelColumnName">
                    Part of the parent relation
                  </span>
                </v-tooltip>
            </template>
            <v-flex pa-2 sm12 md6 lg4>
                <v-card>
                    <v-card-text>
                        <v-text-field ref="columnName" label="Column Name" v-model="columnName"
                                      @blur="saveColumnName($event.target.value)"
                                      :rules="[v => (!allColumnsName.includes(v) || 'The name is taken.'), v => (v !== '' || 'Column should not be empty string')]"
                                      :disabled="value.isLocked || value.name === 'id' || value.type === 'one_to_many' || value.type === 'many_to_many'"/>
                        <v-text-field ref="columnLabel" label="Label" v-model="value.label" :disabled="value.isLocked || value.name === 'id' || isFKColumn" @blur="removeFieldForNewAddedColumn()"/>
                        <v-text-field label="Description" v-model="value.description"  :disabled="value.isLocked || value.name === 'id' || isFKColumn"/>
                        <v-text-field v-if="value.name !== value.oldName" label="Original Column Name" v-model="value.oldName"  disabled/>
                        <v-btn @click="$emit('remove')" color="error" :disabled="value.isLocked || value.name === 'id' || isFKColumn || value.type === 'many_to_many' || value.type === 'one_to_many'">Remove Field</v-btn>
                    </v-card-text>
                </v-card>
            </v-flex>
            <v-flex pa-2 sm12 md6 lg4>
                <v-card>
                    <v-card-text>
                        <v-select
                            :items="filteredColumnTypes"
                            label="Type"
                            v-model="value.type"
                            :disabled="value.isLocked || value.name === 'id' || value.type === 'many_to_many' || value.type === 'one_to_many'  || isFKColumn"
                        />
                        <v-text-field label="Size" v-model.number="value.size"  :disabled="value.isLocked || value.name === 'id' || isFKColumn || value.type === 'pseudo' || value.type === 'many_to_many' || value.type === 'one_to_many'"/>
                        <v-checkbox label="Required?" v-model="value.required"  :disabled="value.isLocked || value.name === 'id' || isFKColumn || value.type === 'pseudo' || value.type === 'many_to_many' || value.type === 'one_to_many'"/>
                        <v-text-field label="Validator" v-model="value.validator"  :disabled="value.isLocked || value.name === 'id' || isFKColumn  || value.type === 'pseudo' || value.type === 'many_to_many' || value.type === 'one_to_many'"/>
                        <v-textarea
                            v-if="value.type === 'pseudo'"
                            label="Pseudo Column Value" v-model="value.pseudoCalc"
                            :disabled="value.isLocked || value.name === 'id' || isFKColumn">
                          <v-tooltip slot="append" bottom max-width="200">
                            <v-icon slot="activator" dark>info</v-icon>
                            <u>Searchable</u>
                            <div>
                              Dot separated string that represent a column of parent <code>parent.parent.name</code>
                            </div>
                            <br/>
                            <u>Non Searchable</u>
                            <div>
                              A php code that has a variable $model represents the current row <code>$model->parent->top_depth + $model->length</code>
                            </div>
                          </v-tooltip>
                        </v-textarea>
                    </v-card-text>
                </v-card>
            </v-flex>
            <v-flex pa-2 sm12 md6 lg4>
                <v-card>
                    <v-card-text>
                        <v-text-field label="Unit" v-model="value.unit"  :disabled="value.isLocked || value.name === 'id' || isFKColumn || value.type === 'many_to_many' || value.type === 'one_to_many'"/>
                        <v-text-field label="Calculate (PHP code)" v-model="value.calculate"  :disabled="value.isLocked || value.name === 'id' || isFKColumn || value.type === 'pseudo' || value.type === 'many_to_many' || value.type === 'one_to_many'"/>
                        <v-text-field label="Default Value" v-model="value.defaultValue"  :disabled="value.isLocked || value.name === 'id' || isFKColumn || value.type === 'pseudo' || value.type === 'many_to_many' || value.type === 'one_to_many'"/>
                    </v-card-text>
                </v-card>
            </v-flex>
        </dis-simple-panel>
    </div>
    <!--"validatorMessage": "",-->
    <!--"unit": "",-->
    <!--"selectListName": "",-->
    <!--"calculate": "",-->
    <!--"defaultValue": ""-->
</template>
<script>
import themeable from 'vuetify/lib/mixins/themeable'
export default {
  name: 'DataModelColumnTemplate',
  mixins: [themeable],
  props: {
    value: {
      type: Object,
      required: true
    },
    isFKColumn: {
      type: Boolean
    },
    parentModelColumnName: {
      type: String
    },
    allColumnsName: {
      type: Array
    }
  },
  created () {
    this.initOldColumnName()
    this.columnName = this.value.name
    if (this.value.newColumnAdded) {
      this.$nextTick(() => this.$refs.columnLabel.focus())
    }
  },
  data () {
    return {
      fullView: false,
      columnName: '',
      wantToEditColumnName: false
    }
  },
  computed: {
    filteredColumnTypes () {
      let types = [
        'integer',
        'double',
        'boolean',
        'string',
        'dateTime',
        'date',
        'time',
        'text',
        'pseudo',
        { text: 'string (multiple)', value: 'string_multiple' },
        { text: 'many (relation)', value: 'many_to_many' },
        { text: 'one - many (relation)', value: 'one_to_many' }
      ]
      let filteredTypes = !['one_to_many', 'many_to_many'].includes(this.value.type)
        ? types.filter(type => type.value !== 'one_to_many' && type.value !== 'many_to_many')
        : types
      return filteredTypes
    }
  },
  methods: {
    initOldColumnName () {
      this.value.oldName = !this.value.oldName ? this.value.name : this.value.oldName
    },
    saveColumnName (value) {
      if (value === '' || this.allColumnsName.includes(value)) {
        this.columnName = this.value.name
      } else {
        this.value.name = value
      }
      this.wantToEditColumnName = false
    },
    editColumnName () {
      if (!this.value.isLocked &&
          this.value.name !== 'id' &&
          this.value.type !== 'one_to_many' &&
          this.value.type !== 'many_to_many' &&
          this.value.name !== this.parentModelColumnName) {
        this.wantToEditColumnName = true
      }
    },
    removeFieldForNewAddedColumn () {
      if (this.value.newColumnAdded) this.$delete(this.value, 'newColumnAdded')
    }
  },
  watch: {
    'value.type': {
      handler: function (newVal, oldVal) {
        if (newVal === 'pseudo' && newVal !== oldVal) {
          const updatedValue = Object.assign(this.value, {
            size: null,
            required: false,
            validator: '',
            calculate: '',
            defaultValue: ''
          })
          this.$emit('input', updatedValue)
        }
      }
    },
    wantToEditColumnName (value) {
      if (value) this.$nextTick(() => this.$refs.columnName.focus())
    }
  }
}
</script>

<style scoped lang="stylus">
</style>
