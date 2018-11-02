import { BrowserModule } from '@angular/platform-browser';
import { NgModule } from '@angular/core';
import { HttpClientModule }    from '@angular/common/http';
import {MatNativeDateModule,MatFormFieldModule, MatInputModule} from '@angular/material';

import { AppComponent  } from './app.component';
import {DialogOverviewExampleDialog} from './app.component';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import {MatDialogModule} from '@angular/material';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

import {DemoMaterialModule} from '../material-module';
 

@NgModule({
  declarations: [
    AppComponent,
    DialogOverviewExampleDialog
  ],
  imports: [
    BrowserModule,
    BrowserAnimationsModule,
    MatDialogModule,
    DemoMaterialModule,
    HttpClientModule,
    MatNativeDateModule,
    FormsModule,
    MatFormFieldModule,
    MatInputModule,
    ReactiveFormsModule

    
  ],
  providers: [],
  bootstrap: [AppComponent],
  entryComponents: [AppComponent,DialogOverviewExampleDialog],
 // declarations:[AppComponent,DialogOverviewExampleDialog]

  
})
export class AppModule { }
