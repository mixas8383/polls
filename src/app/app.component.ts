import { Component, Inject } from '@angular/core';
import { MatDialog, MatDialogRef, MAT_DIALOG_DATA, DateAdapter, MAT_DATE_FORMATS, MAT_DATE_LOCALE } from '@angular/material';
import { PollService } from './poll.service';
import { Poll, PollLocale, CoinLocale, PollCoin } from './poll';
import { DataSource } from '@angular/cdk/collections';
import { FormBuilder, FormGroup, Validators, FormArray, FormControl } from '@angular/forms';
import { MomentDateAdapter } from '@angular/material-moment-adapter';


export interface DialogData {
  animal: string;
  name: string;
}


@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.css']
})
export class AppComponent {
  animal: string;
  name: string;
  constructor(public dialog: MatDialog) { }

  openDialog(): void {
    const dialogRef = this.dialog.open(DialogOverviewExampleDialog, {
      width: '80%',
      data: { name: this.name, animal: this.animal }
    });
    const ttt = 52;

    dialogRef.afterClosed().subscribe(result => {
      console.log('The dialog was closed');
      this.animal = result;
    });
  }

}
export interface PeriodicElement {
  name: string;
  position: number;
  weight: number;
  symbol: string;
}




import * as _moment from 'moment';
// tslint:disable-next-line:no-duplicate-imports
import { default as _rollupMoment } from 'moment';


const moment = _rollupMoment || _moment;

// See the Moment.js docs for the meaning of these formats:
// https://momentjs.com/docs/#/displaying/format/
export const MY_FORMATS = {
  parse: {
    dateInput: 'YYYY-MM-DD',
  },
  display: {
    dateInput: 'YYYY-MM-DD',
    monthYearLabel: 'MMM YYYY',
    dateA11yLabel: 'LL',
    monthYearA11yLabel: 'MMMM YYYY',
  },
};


@Component({
  selector: 'dialog-overview-example-dialog',
  templateUrl: 'app.dialog.html',
  styleUrls: ['app.component.css'],
  providers: [
    // `MomentDateAdapter` can be automatically provided by importing `MomentDateModule` in your
    // application's root module. We provide it at the component level here, due to limitations of
    // our example generation script.
    { provide: DateAdapter, useClass: MomentDateAdapter, deps: [MAT_DATE_LOCALE] },

    { provide: MAT_DATE_FORMATS, useValue: MY_FORMATS },
  ],
})
export class DialogOverviewExampleDialog {
  polls: Poll[];
  displayedColumns: string[] = ['id', 'title', 'admin_title'];
  displayedColumnsNew: string[] = ['id', 'title', 'description', 'admin_title', 'star'];
  displayEdit: boolean = false;
  displayCoinEdit: boolean = false;
  editPollIndex: number;
  poll: FormGroup;
  options: FormGroup;
  currentEditCoin: any;
  localeCodes: string[] = [
    'en', 'ru', 'jp', 'fr',
  ]
  coins: any;

  constructor(
    public dialogRef: MatDialogRef<DialogOverviewExampleDialog>,
    @Inject(MAT_DIALOG_DATA) public data: DialogData
    , private pollService: PollService
    , private fb: FormBuilder) {

  }


  ngOnInit() {
    this.getPolls();
  }

  get pollLocale() {
    return this.options.get('localesFormgroup') as FormArray;
  }
  get pollCoins() {
    return this.options.get('coinsFormgroup') as FormArray;
  }
  get pollCoinslocails() {

    return this.currentEditCoin.get('localesFormGroup') as FormArray;
  }
  editPoll(element) {
    this.editPollIndex = element;
    this.displayEdit = true;
    let locales = this.polls[element].locales;
    console.log(Array.isArray(this.polls[element].locales[0]))
    let localesData = []

    console.log(this.polls[element].locales)
    if (Array.isArray(this.polls[element].locales)) {
      for (let one of this.polls[element].locales) {
        localesData.push(this.fb.group(one));
      }
    } else {
      localesData.push(this.fb.group(this.polls[element].locales));
    }


    let coinsData = [];

    if (Array.isArray(this.polls[element].coins)) {
      for (let one of this.polls[element].coins) {
        if (one.locales) // generate locailsFormArray for coin
        {
          let tempCoinLocales = []
          if (Array.isArray(one.locales)) {
            for (let two of one.locales) {
              tempCoinLocales.push(this.fb.group(two));
            }

          } else {
            tempCoinLocales.push(this.fb.group(one.locales));
          }
          one.localesFormGroup = new FormArray(tempCoinLocales);
        }
        coinsData.push(this.fb.group(one));
      }
    } else {
      coinsData.push(this.fb.group(this.polls[element].coins));
    }




    this.polls[element].localesFormgroup = new FormArray(localesData);
    this.polls[element].coinsFormgroup = new FormArray(coinsData);

    this.options = this.fb.group(this.polls[element]);
    this.coins = this.polls[element].coins;
    console.log(this.coins)
  }

  submitForm() {
    //console.log(this.options)
    console.log((this.options.value))
    this.setPolls(this.options.value);
  }


  getPolls() {
    this.pollService.getPolls().subscribe(polls => {
      this.polls = polls;
      console.log(this.polls)
    });
  }
 setPolls(data) {
    this.pollService.setPolls(data).subscribe(polls => {
      this.polls = polls;
      console.log(this.polls)
    });
  }

  addPollLocale() {
    let locale = new PollLocale;
    let formGroup: FormGroup = new FormGroup({
      title: new FormControl(locale.title),
      locale_code: new FormControl(locale.locale_code),
      description: new FormControl(locale.description),
    });

    this.pollLocale.push(formGroup)
  }

  addCoinLocale() {
    let locale = new CoinLocale;
    let formGroup: FormGroup = new FormGroup({
      id: new FormControl(locale.id),
      title: new FormControl(locale.title),
      coin_id: new FormControl(locale.coin_id),
      locale_code: new FormControl(locale.locale_code),
      description: new FormControl(locale.description),
    });
    this.pollCoinslocails.push(formGroup)
  }


  addCoin() {
    let coin = new PollCoin;

    let locale = new CoinLocale;
    let formGroupl: FormGroup = new FormGroup({
      id: new FormControl(locale.id),
      title: new FormControl(locale.title),
      coin_id: new FormControl(locale.coin_id),
      locale_code: new FormControl(locale.locale_code),
      description: new FormControl(locale.description),
    });

    let formGroup: FormGroup = new FormGroup({
      id: new FormControl(coin.id),
      short_title: new FormControl(coin.short_title),
      poll_id: new FormControl(coin.poll_id),
      market_cap: new FormControl(coin.market_cap),
      ordering: new FormControl(coin.ordering),
      current_price: new FormControl(coin.current_price),
      localesFormGroup:new FormArray([formGroupl]),
    });

    this.pollCoins.push(formGroup)

  }

  onNoClick(): void {
    this.dialogRef.close();
  }

  editCoin(index) {
    this.displayCoinEdit = true;

    console.log(this.pollCoins.controls[index])
    this.currentEditCoin = this.pollCoins.controls[index];
    console.log(this.currentEditCoin);
  }
}