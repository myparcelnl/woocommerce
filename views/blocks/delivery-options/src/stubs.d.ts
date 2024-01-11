/* eslint-disable no-duplicate-imports,@typescript-eslint/no-explicit-any,@typescript-eslint/naming-convention */

type Component = React.JSX.Element<React.JSX.IntrinsicAttributes & {children?: React.ReactNode}>;

declare module '@wordpress/block-editor' {
  declare const useBlockProps: {
    (): any;

    save(): any;
  };

  declare const RichText: any;
}

declare module '@wordpress/blocks' {
  import {type FC} from 'react';

  interface BlocksProps {
    attributes: any;
    checkoutExtensionData: any;
    extensions: any;
  }

  declare function useBlockProps(): any;

  declare function registerBlockType(metadata: any, options: Record<string, any>): void;

  declare type BlocksComponent = FC<BlocksProps>;
}

declare module '@woocommerce/settings' {
  declare function getSetting(name: string): any;

  declare function getSettings(): any[];
}

declare module '@woocommerce/blocks-checkout' {
  import {type ReactNode} from 'react';
  import {type BlocksComponent} from '@wordpress/blocks';

  declare function registerCheckoutBlock(options: {metadata: any; component: BlocksComponent}): void;

  declare const ExperimentalOrderShippingPackages: () => React.JSX.Element<
    React.JSX.IntrinsicAttributes & {children: ReactNode}
  >;
}
