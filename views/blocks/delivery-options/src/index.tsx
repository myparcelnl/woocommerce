import {box} from '@wordpress/icons';
import {registerBlockType} from '@wordpress/blocks';
import metadata from '../block.json';
import {Save} from './save';
import {Edit} from './edit';

registerBlockType(metadata, {
  icon: {
    src: box,
  },
  edit: Edit,
  save: Save,
});
