import {type BlocksComponent} from '@wordpress/blocks';
import {useBlockProps, RichText} from '@wordpress/block-editor';

// eslint-disable-next-line @typescript-eslint/naming-convention
export const Save: BlocksComponent = ({attributes}) => {
  const {text} = attributes;
  return (
    <div {...useBlockProps.save()}>
      <RichText.Content value={text} />
    </div>
  );
};
