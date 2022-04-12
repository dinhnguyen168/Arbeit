<template>
    <div :class="Object.assign({'c-field-template': true}, themeClasses)">
        <div :class="Object.assign({'c-field-template__name': true}, themeClasses)">
            <v-icon v-if="enabled || newField" class="field-drag-handle mr-3">drag_indicator</v-icon>
            <h3 >{{value.name + ` ${isDbRequired ? '*': ''}`}}</h3>
            <v-spacer></v-spacer>
            <v-btn small icon v-if="enabled" @click="fullView = !fullView">
                <v-icon v-if="!this.fullView">arrow_drop_down</v-icon>
                <v-icon v-if="this.fullView">arrow_drop_up</v-icon>
            </v-btn>
        </div>
        <div :class="Object.assign({'c-field-template__form': true}, themeClasses)" v-if="fullView && enabled">
            <v-flex sm12 md6 lg4>
                <v-card>
                    <v-card-title>
                        Visual Options
                    </v-card-title>
                    <v-card-text>
                        <v-text-field label="Label" v-model="value.label" />
                        <v-text-field label="Description" v-model="value.description" />
                        <v-text-field label="Formatter for data table" v-model="value.formatter" hint="A string formatter for the data table, use 'this.' to access column values. Provide an URL to display a link." />
                        <!--<v-text-field label="Group" v-model="value.group" hint="`-` at start of string hide the group name in the form" />-->
                        <!--<v-text-field label="Order" v-model="value.order" />-->
                        <v-checkbox label="Show as additional filter" v-model="value.showAsAdditionalFilter"/>
                    </v-card-text>
                </v-card>
            </v-flex>
            <v-flex pl-2 pr-2 sm12 md6 lg4>
                <v-card>
                    <v-card-title>
                        Input Options
                    </v-card-title>
                    <v-card-text>
                        <input-template v-model="value.formInput" :dataModelFullName="dataModelFullName" :dataModelColumnName="dataModelColumnName"/>
                    </v-card-text>
                </v-card>
            </v-flex>
            <v-flex pl-2 pr-2 sm12 md6 lg4>
                <v-card>
                    <v-card-title>
                        Input Validators
                    </v-card-title>
                    <v-card-text>
                        <validators-select v-bind="$attrs" v-model="value.validators" :dataModelFullName="dataModelFullName" :dataModelColumnName="dataModelColumnName" :is-db-required="isDbRequired"/>
                    </v-card-text>
                </v-card>
            </v-flex>
        </div>
        <!--<template >-->
            <!---->
            <!--&lt;!&ndash;<v-flex pl-2 pr-2 sm12 md6 lg12>&ndash;&gt;-->
                <!--&lt;!&ndash;<v-card>&ndash;&gt;-->
                    <!--&lt;!&ndash;<v-card-title>&ndash;&gt;-->
                        <!--&lt;!&ndash;Other&ndash;&gt;-->
                    <!--&lt;!&ndash;</v-card-title>&ndash;&gt;-->
                    <!--&lt;!&ndash;<v-card-text>&ndash;&gt;-->
                        <!--&lt;!&ndash;<v-text-field label="Group" v-model="value.group" hint="`-` at start of string hide the group name in the form" />&ndash;&gt;-->
                    <!--&lt;!&ndash;</v-card-text>&ndash;&gt;-->
                <!--&lt;!&ndash;</v-card>&ndash;&gt;-->
            <!--&lt;!&ndash;</v-flex>&ndash;&gt;-->
        <!--</template>-->
    </div>
</template>

<script>
import ValidatorsSelect from './ValidatorsTemplate'
import InputTemplate from './InputTemplate'
import themeable from 'vuetify/lib/mixins/themeable'
export default {
  name: 'FieldTemplate',
  mixins: [themeable],
  components: { InputTemplate, ValidatorsSelect },
  props: {
    enabled: {
      type: Boolean,
      default: true
    },
    newField: {
      type: Boolean,
      default: false
    },
    value: {
      type: Object,
      required: true
    },
    isDbRequired: {
      type: Boolean
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
      fullView: false
    }
  }
}
</script>
