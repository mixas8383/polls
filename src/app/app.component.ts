import { Component, Inject } from '@angular/core';
import { MatDialog, MatDialogRef, MAT_DIALOG_DATA, DateAdapter, MAT_DATE_FORMATS, MAT_DATE_LOCALE, MAT_SNACK_BAR_DEFAULT_OPTIONS, MatTableDataSource } from '@angular/material';
import { PollService } from './poll.service';
import { Poll, PollLocale, CoinLocale, PollCoin } from './poll';
import { DataSource } from '@angular/cdk/collections';
import { FormBuilder, FormGroup, Validators, FormArray, FormControl } from '@angular/forms';
import { MomentDateAdapter } from '@angular/material-moment-adapter';
import { SnackBarOverview } from './app.snackbar';
import { MessageService } from './message.service';


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
      height: '80%',
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
    { provide: MAT_SNACK_BAR_DEFAULT_OPTIONS, useValue: { duration: 10000 } }
  ],
})
export class DialogOverviewExampleDialog {
  polls: Poll[];
  displayedColumns: string[] = ['id', 'title', 'admin_title'];
  displayedColumnsNew: string[] = ['id', 'title', 'description', 'admin_title', 'star','star2'];
  datasource = new MatTableDataSource(this.polls)
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
    , private snackBar: SnackBarOverview
    , private messageService: MessageService
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

    if (element === false) {

      let newPoll = new Poll;

      newPoll.admin_title = '';
      newPoll.restrict_user = 0;
      newPoll.restrict_ip = 0;
      newPoll.restrict_ip_limit = 0;
      newPoll.active = 0;
      newPoll.start_date = '';
      newPoll.end_date = '';


      let newLocale = new PollLocale;
      newLocale.title = '';
      newLocale.description = '';
      newLocale.locale_code = '';
      newPoll.locales = [newLocale];


      let newCoins = new PollCoin;
      newCoins.short_title = ''
      newCoins.icon = ''
      newCoins.file = ''
      newCoins.market_cap = ''
      newCoins.ordering = 0;
      newCoins.poll_id = 0;
      newCoins.current_price = '';

      let cnewCoinLang = new CoinLocale;
      cnewCoinLang.title = '';
      cnewCoinLang.description = '';
      cnewCoinLang.locale_code = '';
      cnewCoinLang.coin_id = 0;

      newCoins.locales = [cnewCoinLang];



      newPoll.coins = [newCoins];


      this.polls.push(newPoll);



      element = this.polls.length - 1;
      console.log(this.polls);
    }


    this.editPollIndex = element;
    this.displayEdit = true;
    let locales = this.polls[element].locales;
    let localesData = []

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
        one.file = '';
        coinsData.push(this.fb.group(one));
      }
    } else {
      coinsData.push(this.fb.group(this.polls[element].coins));
    }

    this.polls[element].localesFormgroup = new FormArray(localesData);
    this.polls[element].coinsFormgroup = new FormArray(coinsData);
    this.options = this.fb.group(this.polls[element]);
    this.coins = this.polls[element].coins;
    this.options.controls.restrict_user.setValue((this.polls[element].restrict_user * 1) ? true : false);
    this.options.controls.restrict_ip.setValue((this.polls[element].restrict_ip * 1) ? true : false);

  }

  submitForm() {
    //console.log(this.options)
    console.log((this.options.value))
    delete this.options.value['coins'];
    delete this.options.value['locales'];
    this.setPolls(this.options.value);
    //snackBar

  }


  getPolls() {
    return this.pollService.getPolls().subscribe(polls => {
      this.polls = polls;
      this.datasource.data=this.polls;
    });
  }

  showPrimariesList() {

    this.displayEdit = false;
    this.displayCoinEdit = false;
  }

  setPolls(data) {

    this.pollService.setPolls(data).subscribe(response => {
      this.snackBar.openSnackBar(response['message'], 'Close');
      this.getPolls();
      this.showPrimariesList();
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
      localesFormGroup: new FormArray([formGroupl]),
      file: new FormControl(coin.file),
      icon: new FormControl(coin.icon),
    });

    this.pollCoins.push(formGroup)

  }

  onNoClick(): void {
    this.dialogRef.close();
  }

  editCoin(index) {
    this.displayCoinEdit = true;
    this.currentEditCoin = this.pollCoins.controls[index];
  }
  removeCoin(index) {
    this.pollCoins.removeAt(index);

  }
  removePoll(index)
  {
    console.log(index)
    console.log(this.polls)
    this.polls.splice(index,1);
    this.getPolls();
  }
  onFileChange(event) {
    const reader = new FileReader();

    if (event.target.files && event.target.files.length) {
      const [file] = event.target.files;
      reader.readAsDataURL(file);
      //reader.readAsBinaryString(file)
      reader.onload = () => {
        this.currentEditCoin.patchValue({
          file: reader.result
        });

      };
    }
  }

}