import {receipt} from '@wordpress/icons';
import {registerBlockType} from '@wordpress/blocks';
import metadata from '../block.json';
import {Edit} from './edit';

registerBlockType(metadata, {
  icon: {
    src: receipt,
  },
  edit: Edit,
});
