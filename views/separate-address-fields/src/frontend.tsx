/* eslint-disable no-underscore-dangle */
import React from 'react';
import {useBlockProps} from '@wordpress/block-editor';
import {registerCheckoutBlock, ValidatedTextInput} from '@woocommerce/blocks-checkout';
import metadata from '../block.json';

type Attributes = Record<string, unknown>;

interface Props {
  attributes: Attributes;
  setAttributes(attributes: Attributes): void;
}

const Block: React.FC<Props> = ({attributes, setAttributes}) => {
  const blockProps = useBlockProps();
  return (
    <div {...blockProps}>
      Block
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

registerCheckoutBlock({metadata, component: Block});
