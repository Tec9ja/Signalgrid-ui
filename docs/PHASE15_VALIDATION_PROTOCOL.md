# SignalGrid Scientific Validation Protocol

## Minimum pilot evidence
- One selected AAUA floor with a calibrated floor plan and usable boundary.
- At least 30 direct RSSI points for a full-floor pilot.
- At least two survey periods under documented device and environmental conditions.
- One before-and-after intervention comparison.
- AP-load telemetry where available.
- User feedback correlated only within the documented location and time window.

## Interpolation evaluation
Use deterministic systematic holdout validation at 20 percent. Report MAE, RMSE, mean bias error and maximum absolute error in dB. Never combine simulated measurements with validation evidence.

## Reproducibility record
Record device name, operating platform, collector version, date, time, occupancy, doors/windows, unusual interference, weather where relevant, and changes to AP placement or configuration.

## Acceptance gates
- Automated PHPUnit suite passes.
- Vite production build passes.
- Database and private storage health checks pass.
- Project alignment identifies Adekunle Ajasin University, Akungba.
- PDF and CSV export tests pass.
- Authorization tests prevent Viewer accounts from performing management actions.
