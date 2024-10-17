import React from 'react';
import {useBlockProps} from '@wordpress/block-editor';

// eslint-disable-next-line @typescript-eslint/naming-convention
export const Edit: React.FC = () => {
  const blockProps = useBlockProps();

  return (
    <div
      {...blockProps}
      style={{display: 'block'}}>
      MyParcel Delivery Options
    </div>
  );
};
