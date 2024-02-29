import {type BlocksComponent} from '@wordpress/blocks';
import {useBlockProps} from '@wordpress/block-editor';

export const Edit: BlocksComponent = () => {
  const blockProps = useBlockProps();
  return (
    <div
      {...blockProps}
      style={{display: 'block'}}>
      MyParcel Delivery Options{' '}
    </div>
  );
};
