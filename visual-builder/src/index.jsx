import React from 'react';
import metadata from './module.json';

const { addAction, addFilter } = window.vendor.wp.hooks;
const { ModuleContainer, StyleContainer, elementClassnames } = window.divi.module;
const { registerModule } = window.divi.moduleLibrary;
const pluginNamespace = 'servinderSubsitesDivi';

const containsLoopSetting = (value) => {
  if (!value || typeof value !== 'object') return false;
  return Object.entries(value).some(([key, child]) => key === 'loop' || containsLoopSetting(child));
};

const supportsLoop = (metadata) => (
  ['divi/group', 'divi/group-carousel'].includes(metadata?.name)
  || containsLoopSetting(metadata?.attributes?.module?.settings)
);

const targetOptions = window.ServinderSubsitesDiviSettings?.subsiteOptions ?? [];

addFilter('divi.moduleLibrary.moduleSettings.groups', pluginNamespace, (groups, metadata) => {
  if (!supportsLoop(metadata)) return groups;
  return {
    ...groups,
    servinderSubsitesLoop: {
      groupName: 'servinderSubsitesLoop',
      panel: 'content',
      priority: 96,
      multiElements: true,
      component: {
        name: 'divi/composite',
        props: { groupLabel: 'Visibilidade por subsite' },
      },
    },
  };
});

addFilter('divi.moduleLibrary.moduleAttributes', pluginNamespace, (attributes, metadata) => {
  if (!supportsLoop(metadata)) return attributes;
  return {
    ...attributes,
    servinderSubsiteTargets: {
      type: 'object',
      selector: '{{selector}}',
      default: { innerContent: { desktop: { value: [] } } },
      settings: {
        innerContent: {
          groupType: 'group-item',
          item: {
            attrName: 'servinderSubsiteTargets.innerContent',
            label: 'Subsites',
            description: 'Escolha os subsites nos quais este módulo com Loop ficará visível.',
            groupSlug: 'servinderSubsitesLoop',
            priority: 1,
            render: true,
            features: { sticky: false, responsive: false, dynamicContent: false, preset: 'content' },
            component: { name: 'divi/checkboxes', type: 'field', props: { options: targetOptions } },
          },
        },
      },
      attributes: {},
      childrenSanitizer: 'et_core_esc_previously',
    },
    servinderSubsiteMode: {
      type: 'object',
      selector: '{{selector}}',
      default: { innerContent: { desktop: { value: 'all' } } },
      settings: {
        innerContent: {
          groupType: 'group-item',
          item: {
            attrName: 'servinderSubsiteMode.innerContent',
            label: 'Mostrar em',
            description: 'Define onde este módulo e os dados do Loop aparecem.',
            groupSlug: 'servinderSubsitesLoop',
            priority: 2,
            render: true,
            features: { sticky: false, responsive: false, dynamicContent: false, preset: 'content' },
            component: {
              name: 'divi/select',
              type: 'field',
              props: {
                options: [
                  { label: 'Todos os subsites', value: 'all' },
                  { label: 'Somente os selecionados', value: 'include' },
                  { label: 'Todos, exceto os selecionados', value: 'exclude' },
                ],
              },
            },
          },
        },
      },
      attributes: {},
      childrenSanitizer: 'et_core_esc_previously',
    },
  };
});

addFilter(
  'divi.module.layout.childModule.loop.resultsQueryParams',
  pluginNamespace,
  (queryParams, attrs, moduleId, queryType) => {
    if ('post_types' !== queryType) return queryParams;
    const mode = attrs?.servinderSubsiteMode?.innerContent?.desktop?.value ?? 'all';
    const ids = attrs?.servinderSubsiteTargets?.innerContent?.desktop?.value ?? [];
    queryParams.set('servinder_subsite_mode', mode);
    queryParams.set('servinder_subsite_ids', Array.isArray(ids) ? ids.join(',') : '');
    const currentSubsiteId = Number(window.ServinderSubsitesDiviSettings?.currentSubsiteId ?? 0);
    if (currentSubsiteId > 0) queryParams.set('servinder_subsite_id', String(currentSubsiteId));
    return queryParams;
  },
);

const ModuleStyles = ({ attrs, elements, settings, mode, state, noStyleTag }) => (
  <StyleContainer mode={mode} state={state} noStyleTag={noStyleTag}>
    {elements.style({
      attrName: 'module',
      styleProps: { disabledOn: { disabledModuleVisibility: settings?.disabledModuleVisibility } },
    })}
    {elements.style({ attrName: 'content' })}
  </StyleContainer>
);

const moduleClassnames = ({ classnamesInstance, attrs }) => {
  classnamesInstance.add(elementClassnames({ attrs: attrs?.module?.decoration ?? {} }));
};

const conditionalContent = {
  metadata,
  renderers: {
    edit: ({ attrs, id, name, elements }) => (
      <ModuleContainer
        attrs={attrs}
        elements={elements}
        id={id}
        moduleClassName="servinder_subsites_divi_conditional_content"
        name={name}
        stylesComponent={ModuleStyles}
        classnamesFunction={moduleClassnames}
      >
        {elements.styleComponents({ attrName: 'module' })}
        <div className="et_pb_module_inner">
          {elements.render({ attrName: 'content' })}
        </div>
      </ModuleContainer>
    ),
  },
  placeholderContent: {
    targetIds: { innerContent: { desktop: { value: [] } } },
    visibilityMode: { innerContent: { desktop: { value: 'include' } } },
    content: { innerContent: { desktop: { value: '<p>Adicione aqui o conteúdo específico deste subsite.</p>' } } },
  },
};

if (Array.isArray(targetOptions)) {
  metadata.attributes.targetIds.settings.innerContent.item.component.props.options = targetOptions;
}

addAction('divi.moduleLibrary.registerModuleLibraryStore.after', 'servinderSubsitesDivi', () => {
  registerModule(conditionalContent.metadata, conditionalContent);
});
