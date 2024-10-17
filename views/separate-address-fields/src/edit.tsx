import React from 'react';
import {useBlockProps} from '@wordpress/block-editor';
import {ValidatedTextInput} from '@woocommerce/blocks-checkout';

type Attributes = Record<string, unknown>;

interface Props {
  attributes: Attributes;
  setAttributes(attributes: Attributes): void;
}

// eslint-disable-next-line @typescript-eslint/naming-convention
export const Edit: React.FC<Props> = ({attributes, setAttributes}) => {
  const blockProps = useBlockProps();
  return (
    <div {...blockProps}>
      Edit Block
      <div className={'example-fields'}>
        <ValidatedTextInput
          id="gift_message"
          type="text"
          required={false}
          className={'gift-message'}
          label={'Gift Message'}
          value={''}
        />
      </div>
    </div>
  );
};
