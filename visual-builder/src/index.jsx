import React from 'react';
import metadata from './module.json';

const { addAction } = window.vendor.wp.hooks;
const { ModuleContainer, StyleContainer, elementClassnames } = window.divi.module;
const { registerModule } = window.divi.moduleLibrary;

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

const targetOptions = window.ServinderSubsitesDiviSettings?.subsiteOptions;
if (Array.isArray(targetOptions)) {
  metadata.attributes.targetIds.settings.innerContent.item.component.props.options = targetOptions;
}

addAction('divi.moduleLibrary.registerModuleLibraryStore.after', 'servinderSubsitesDivi', () => {
  registerModule(conditionalContent.metadata, conditionalContent);
});
