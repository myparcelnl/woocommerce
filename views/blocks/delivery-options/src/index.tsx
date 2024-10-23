import {box} from '@wordpress/icons';
import {registerBlockType} from '@wordpress/blocks';
import metadata from '../block.json';
import packageJson from '../../../../package.json';
import {Save} from './save';
import {Edit} from './edit';

registerBlockType(metadata, {
  version: packageJson.version,
  icon: {
    src: box,
  },
  edit: Edit,
  save: Save,
});
