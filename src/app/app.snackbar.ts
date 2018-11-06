import { Injectable} from '@angular/core';
import {MatSnackBar} from '@angular/material';

/**
 * @title Basic snack-bar
 */
@Injectable({ providedIn: 'root' })
export class SnackBarOverview {
  constructor(public snackBar: MatSnackBar) {}

  openSnackBar(message: string, action: string) {
    this.snackBar.open(message, action, {
      duration: 2000,
    });
  }
}