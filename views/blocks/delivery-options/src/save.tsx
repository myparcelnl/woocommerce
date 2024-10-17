import React from 'react';
import {type BlockSaveProps} from '@wordpress/blocks';
import {useBlockProps, RichText} from '@wordpress/block-editor';
import {type DeliveryOptionsBlockAttributes} from './types';

// eslint-disable-next-line @typescript-eslint/naming-convention
export const Save: React.FC<BlockSaveProps<DeliveryOptionsBlockAttributes>> = ({attributes}) => {
  const {text} = attributes;

  return (
    <div {...useBlockProps.save()}>
      <RichText.Content value={text} />
    </div>
  );
};
