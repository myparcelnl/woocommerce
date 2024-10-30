import {describe, it, expect} from 'vitest';
import {splitStreet, type StreetParts} from './splitStreet';

type TestInput = [string, Partial<StreetParts>];

describe('splitStreet', () => {
  it.each([
    ['Antareslaan 31', {street: 'Antareslaan', number: '31'}],
    ['Antareslaan 31A', {street: 'Antareslaan', number: '31', numberSuffix: 'A'}],
    ['Antareslaan 31 A', {street: 'Antareslaan', number: '31', numberSuffix: 'A'}],
    ['Plein 1940-45 1', {street: 'Plein 1940-45', number: '1'}],
    ['Plein 1940-45 1A', {street: 'Plein 1940-45', number: '1', numberSuffix: 'A'}],
    ['Plein 1940-45 1 A', {street: 'Plein 1940-45', number: '1', numberSuffix: 'A'}],
    ['Alleestraat 34/2', {street: 'Alleestraat', number: '34', numberSuffix: '2'}],
    ['Alleestraat 34/a', {street: 'Alleestraat', number: '34', numberSuffix: 'a'}],
    ['234th Boop. street 20 a', {street: '234th Boop. street', number: '20', numberSuffix: 'a'}],

    ['Graan voor Visch 19905', {street: 'Graan voor Visch', number: '19905', numberSuffix: ''}],
    ['Charles Petitweg 7 A-2', {street: 'Charles Petitweg', number: '7', numberSuffix: 'A-2'}],
    ['overtoom 452-2', {street: 'overtoom', number: '452', numberSuffix: '2'}],
    ['avenue roger lallemand 13 B13', {street: 'avenue roger lallemand', number: '13', numberSuffix: '13'}],
    ['A 109', {street: 'A', number: '109', numberSuffix: ''}],
    ['Plein 1945 27', {street: 'Plein 1945', number: '27', numberSuffix: ''}],
    ['Plein 1940-45 3 b', {street: 'Plein 1940-45', number: '3', numberSuffix: 'b'}],
    ['Laan 1940-1945 103', {street: 'Laan 1940-1945', number: '103', numberSuffix: ''}],
    ['Wijk 1 20', {street: 'Wijk 1', number: '20', numberSuffix: ''}],
    ['300 laan 3', {street: '300 laan', number: '3', numberSuffix: ''}],
    ['A.B.C. street 12', {street: 'A.B.C. street', number: '12', numberSuffix: ''}],
    ['street street 269 133', {street: 'street street 269', number: '133', numberSuffix: ''}],
    ['Abeelstreet H 10', {street: 'Abeelstreet H', number: '10', numberSuffix: ''}],
    ['street street 269 1001', {street: 'street street 269', number: '1001', numberSuffix: ''}],
    ['Meijhorst 50e 26', {street: 'Meijhorst 50e', number: '26', numberSuffix: ''}],
    ['street street 12 ZW', {street: 'street street', number: '12', numberSuffix: 'ZW'}],
    ['street 12', {street: 'street', number: '12', numberSuffix: ''}],
    ['Biltstreet 113 A BS', {street: 'Biltstreet', number: '113', numberSuffix: 'A BS'}],
    ['Zonegge 23 12', {street: 'Zonegge 23', number: '12', numberSuffix: ''}],
    ['Markerkant 10 142', {street: 'Markerkant 10', number: '142', numberSuffix: ''}],
    ['Markerkant 10 11e', {street: 'Markerkant', number: '10', numberSuffix: '11e'}],
    ['Sir Winston Churchillln 283 F008', {street: 'Sir Winston Churchillln', number: '283', numberSuffix: 'F008'}],
    ['Sir Winston Churchillln 283 -9', {street: 'Sir Winston Churchillln', number: '283', numberSuffix: '9'}],
    ['Insulindestreet 69 B03', {street: 'Insulindestreet', number: '69', numberSuffix: 'B03'}],
    ['Scheepvaartlaan 34 302', {street: 'Scheepvaartlaan 34', number: '302', numberSuffix: ''}],
    ['oan e dijk 48', {street: 'oan e dijk', number: '48', numberSuffix: ''}],
    ['Vlinderveen 137', {street: 'Vlinderveen', number: '137', numberSuffix: ''}],
    ['street 39- 1 hg', {street: 'street 39-', number: '1', numberSuffix: 'hg'}],
    ['Nicolaas Ruyschstraat 8 02L', {street: 'Nicolaas Ruyschstraat', number: '8', numberSuffix: '02L'}],
    ['Landsdijk 49 A', {street: 'Landsdijk', number: '49', numberSuffix: 'A'}],
    ['Markerkant 10 apartment a', {street: 'Markerkant', number: '10', numberSuffix: 'a'}],
    ['Markerkant 10 noordzijde', {street: 'Markerkant', number: '10', numberSuffix: 'NZ'}],
    ['Markerkant 10 west', {street: 'Markerkant', number: '10', numberSuffix: 'W'}],
    ['Tuinstraat 35 boven', {street: 'Tuinstraat', number: '35', numberSuffix: 'boven'}],
    ['Nicolaas Ruyschstraat 8 ad hoc', {street: 'Nicolaas Ruyschstraat', number: '8', numberSuffix: 'ad hoc'}],
    ['Hoofdweg 679 A', {street: 'Hoofdweg', number: '679', numberSuffix: 'A'}],
    ['Manebruggestraat 316 bus 2 R', {street: 'Manebruggestraat', number: '316', numberSuffix: '2 R'}],
    ['Eikenstraat 1 Bus2', {street: 'Eikenstraat', number: '1', numberSuffix: '2'}],
    ['Klappoel 1b, Bus5', {street: 'Klappoel', number: '1', numberSuffix: '5'}],
    ['Klappoel 1b/bus5', {street: 'Klappoel', number: '1', numberSuffix: '5'}],
    ['Zennestraat 32 bte 20', {street: 'Zennestraat', number: '32', numberSuffix: '20'}],
    ['Zennestraat 32 bus 20', {street: 'Zennestraat', number: '32', numberSuffix: '20'}],
    ['Zennestraat 32 box 32', {street: 'Zennestraat', number: '32', numberSuffix: '32'}],
    ['Zennestraat 32 boîte 20', {street: 'Zennestraat', number: '32', numberSuffix: '20'}],
    ['Dendermondestraat 55 bus 12', {street: 'Dendermondestraat', number: '55', numberSuffix: '12'}],
    ['Steengroefstraat 21 bus 27', {street: 'Steengroefstraat', number: '21', numberSuffix: '27'}],
    ['Philippe de Champagnestraat 23', {street: 'Philippe de Champagnestraat', number: '23', numberSuffix: ''}],
    ['Straat 23-C11', {street: 'Straat', number: '23', numberSuffix: 'C11'}],
    ['Kortenberglaan 4 bus 10', {street: 'Kortenberglaan', number: '4', numberSuffix: '10'}],
    ['Ildefonse Vandammestraat 5 D', {street: 'Ildefonse Vandammestraat', number: '5', numberSuffix: 'D'}],
    ['I. Vandammestraat 5 D', {street: 'I. Vandammestraat', number: '5', numberSuffix: 'D'}],
    ['Slameuterstraat 9B', {street: 'Slameuterstraat', number: '9', numberSuffix: 'B'}],
    ['Oud-Dorpsstraat 136 3', {street: 'Oud-Dorpsstraat', number: '136', numberSuffix: '3'}],
    ['Groenstraat 16 C', {street: 'Groenstraat', number: '16', numberSuffix: 'C'}],
    ['Brusselsesteenweg 30 /0101', {street: 'Brusselsesteenweg', number: '30', numberSuffix: '0101'}],
    ['Onze-Lieve-Vrouwstraat 150/1', {street: 'Onze-Lieve-Vrouwstraat', number: '150', numberSuffix: '1'}],
    ['Wilgenstraat 6/1', {street: 'Wilgenstraat', number: '6', numberSuffix: '1'}],

    // Can't split this so expect the full street back
    ['2372 impossible st.', {}],
  ] satisfies TestInput[])('should split street "%s" into parts', (input, output) => {
    const defaults = {
      street: input,
      number: '',
      numberSuffix: '',
    };

    expect({input, ...splitStreet(input)}).toEqual({input, ...defaults, ...output});
  });
});
