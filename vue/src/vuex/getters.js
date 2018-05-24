import { WSAETIMEDOUT } from 'constants';

var tinycolor = require('tinycolor2')

const getters = {
  breakpoint: state => {
    return state.breakpoint.breakpoint
  },

  breakpointAndDimensions: state => {
    return state.breakpoint
  },

  // This takes a component name and returns the corresponding component from
  // deviseSettings.$deviseComponents. This contains the name, template, and field
  // configuration.
  component: state => (name) => {
    return deviseSettings.$deviseComponents[name]
  },

  componentFromView: state => (view) => {
    for (var component in deviseSettings.$deviseComponents) {
      if (deviseSettings.$deviseComponents[component].view === 'slices.' + view) {
        return deviseSettings.$deviseComponents[component]
      }
    }
    return false
  },

  interface: state => {
    return deviseSettings.$interface
  },

  sliceConfig: state => (slice) => {
    return deviseSettings.$deviseComponents[slice.metadata.name] ? deviseSettings.$deviseComponents[slice.metadata.name] : deviseSettings.$deviseComponents[slice.name]
  },

  fieldConfig: (state, getters) => ({fieldKey, slice}) => {
    let sliceConfig = getters.sliceConfig(slice)
    if (typeof sliceConfig.config[fieldKey] === 'undefined') {
      throw new ReferenceError(`Could not find the configuration for the ${fieldKey} fieldkey from this slice: ${sliceConfig.name}`)
    }
    return sliceConfig.config[fieldKey]
  },

  // Languages
  languages: state => {
    return state.languages
  },

  // Media manager

  files: state => {
    return state.files
  },

  directories: state => {
    return state.directories
  },

  currentDirectory: state => {
    return state.currentDirectory
  },

  // Meta
  meta: state => {
    return state.meta
  },

  // Models
  storeModels: state => {
    return state.models
  },

  modelSettings: state => {
    return state.modelSettings
  },

  // Pages
  pages: state => {
    return state.pages
  },

  page: (state, getters, rootState) => {
    var id = parseInt(rootState.route.params.pageId)
    return state.pages.data.find(page => page.id === id)
  },

  currentPage: (state, getters, rootState) => {
    return deviseSettings.$page
  },

  // Sites
  sites: state => {
    return state.sites
  },

  site: (state, getters, rootState) => {
    var id = parseInt(rootState.route.params.siteId)
    return getters.siteById(id)
  },

  siteById: state => (id) => {
    return state.sites.data.find(site => site.id === id)
  },
  
  theme: (state, getters, rootState) => {
    if (state.page) {
      let site = getters.siteById(state.page.site_id)
      var colors = site.settings.colors

      colors.buttonsInverseLeft = {
        color: tinycolor(colors.buttonsActionLeft.color).spin(90).toString()
      }

      colors.buttonsInverseRight = {
        color: tinycolor(colors.buttonsActionRight.color).spin(90).toString()
      }

      colors.buttonsInverseText = {
        color: tinycolor(colors.buttonsActionText.color).spin(90).toString()
      }
      return colors
    }
    return {}
  },

  // Slices
  slicesList: state => {
    return state.slices
  },

  slicesDirectories: state => {
    return state.slicesDirectories
  },

  // Templates
  templates: state => {
    return state.templates
  },

  template: (state, getters, rootState) => {
    var id = parseInt(rootState.route.params.templateId)
    return state.templates.data.find(template => template.id === id)
  },

  // Users
  users: state => {
    return state.users
  },

  user: (state, getters, rootState) => {
    var id = parseInt(rootState.route.params.userId)
    return state.users.data.find(user => user.id === id)
  }
}

export default getters
