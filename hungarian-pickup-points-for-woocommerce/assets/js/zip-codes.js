//Simple geolocate function based on ZIP codes, just to zoom in on the state
const vp_woo_pont_state_postcodes = {'BU':[1011,1012,1013,1014,1015,1016,1021,1022,1023,1024,1025,1026,1027,1028,1029,1031,1032,1033,1034,1035,1036,1037,1038,1039,1041,1042,1043,1044,1045,1046,1047,1048,1051,1052,1053,1054,1055,1056,1061,1062,1063,1064,1065,1066,1067,1068,1069,1071,1072,1073,1074,1075,1076,1077,1078,1081,1082,1083,1084,1085,1086,1087,1088,1089,1091,1092,1093,1094,1095,1096,1097,1098,1101,1102,1103,1104,1105,1106,1107,1108,1111,1112,1113,1114,1115,1116,1117,1118,1119,1121,1122,1123,1124,1125,1126,1131,1132,1133,1134,1135,1136,1137,1138,1139,1141,1142,1143,1144,1145,1146,1147,1148,1149,1151,1152,1153,1154,1155,1156,1157,1158,1161,1162,1163,1164,1165,1171,1172,1173,1174,1181,1182,1183,1184,1185,1186,1188,1191,1192,1193,1194,1195,1196,1201,1202,1203,1204,1205,1211,1212,1213,1214,1215,1221,1222,1223,1224,1225,1237,1238,1239,1529],'PE':[2000,2009,2011,2014,2015,2016,2017,2021,2022,2023,2024,2025,2026,2030,2035,2036,2040,2049,2051,2053,2071,2072,2074,2080,2081,2084,2085,2086,2087,2089,2092,2093,2094,2095,2097,2098,2099,2100,2111,2112,2113,2114,2115,2116,2117,2118,2119,2120,2142,2143,2144,2145,2163,2164,2165,2167,2170,2173,2174,2181,2183,2184,2185,2191,2192,2193,2194,2209,2211,2212,2213,2214,2215,2216,2217,2220,2233,2234,2235,2251,2252,2253,2254,2255,2300,2310,2314,2315,2317,2318,2319,2321,2322,2330,2335,2336,2337,2338,2339,2340,2345,2347,2351,2360,2363,2365,2366,2367,2370,2371,2373,2375,2376,2378,2381,2440,2461,2510,2600,2613,2614,2615,2621,2623,2626,2627,2628,2629,2631,2632,2633,2634,2635,2637,2638,2639,2681,2683,2700,2711,2712,2713,2721,2723,2724,2730,2735,2736,2737,2738,2740,2746,2747,2760,2764,2765,2766,2767,2768,2769,2898,3356,3604,3630,3647,3775,3874,3905,3906,4836,6332,6781,7092,7163,7212,7954,7981,8193,8292,8321,8351,8357,8619,8640,8873,8881,8929,9651,9707,9791,9825],'VA':[2013,2027,2038,2039,2182,2532,2745,3212,3422,3462,3622,3623,3672,3757,3762,3795,3933,3992,4274,4761,4821,4942,4971,5062,5065,5091,5324,5622,6422,6784,7300,7385,7511,7517,7678,7763,7833,7847,7935,7957,8122,8428,8863,9325,9354,9500,9511,9514,9515,9516,9517,9521,9522,9523,9531,9541,9544,9545,9547,9548,9549,9551,9552,9553,9554,9555,9556,9561,9600,9608,9609,9611,9622,9623,9624,9625,9631,9632,9633,9634,9635,9641,9643,9652,9653,9654,9661,9662,9663,9664,9665,9671,9672,9673,9674,9683,9684,9685,9721,9722,9724,9725,9726,9733,9734,9735,9737,9739,9741,9742,9743,9744,9745,9746,9747,9748,9751,9752,9754,9756,9757,9761,9762,9763,9764,9766,9771,9772,9774,9775,9776,9777,9781,9782,9783,9784,9789,9792,9793,9794,9795,9796,9797,9798,9799,9800,9811,9812,9813,9814,9821,9823,9826,9831,9832,9833,9834,9835,9836,9841,9842,9900,9909,9912,9915,9917,9918,9919,9921,9922,9923,9931,9932,9934,9936,9937,9938,9941,9942,9944,9945,9946,9951,9952,9953,9955,9961,9962,9970,9981,9982,9983,9985],'KE':[2028,2067,2146,2225,2242,2500,2509,2517,2518,2519,2522,2523,2524,2525,2526,2527,2529,2531,2533,2534,2536,2537,2541,2543,2544,2545,2800,2821,2823,2824,2831,2832,2833,2834,2835,2836,2837,2852,2854,2856,2858,2859,2861,2862,2870,2879,2881,2882,2883,2884,2885,2886,2887,2888,2890,2897,2899,2911,2931,2941,2942,2943,2944,2945,2946,2947,2948,2949,3374,3910,4145,4965,5536,5539,7200,7334,7668,8625,8736],'ZA':[2045,2161,2241,2610,2644,2671,2698,2889,3263,3574,3716,3721,3765,3886,5125,5624,5920,6077,6640,7025,7272,7285,7386,7695,7761,7966,8072,8313,8314,8315,8316,8341,8353,8354,8355,8356,8360,8371,8372,8373,8380,8391,8392,8393,8394,8429,8475,8477,8479,8563,8595,8716,8741,8742,8743,8745,8746,8747,8749,8751,8752,8753,8754,8756,8761,8762,8764,8765,8767,8771,8772,8773,8774,8776,8777,8778,8782,8784,8785,8788,8789,8790,8792,8793,8795,8797,8798,8799,8800,8808,8809,8821,8822,8824,8825,8827,8831,8834,8835,8855,8856,8861,8862,8866,8868,8872,8874,8879,8882,8883,8885,8886,8887,8888,8891,8893,8900,8911,8912,8913,8914,8915,8917,8918,8919,8921,8923,8924,8925,8931,8932,8934,8935,8936,8943,8944,8945,8946,8947,8948,8949,8951,8953,8954,8956,8957,8958,8960,8966,8969,8971,8973,8975,8976,8977,8978,8981,8983,8984,8985,8986,8988,8990,8991,8992,8994,8995,8996,8997,8998,8999,9235,9300,9324,9542,9612,9621,9636,9727,9738],'FE':[2060,2063,2064,2065,2066,2091,2400,2407,2421,2422,2423,2424,2425,2426,2427,2428,2431,2432,2433,2434,2435,2451,2453,2454,2455,2456,2457,2459,2462,2465,2471,2472,2473,2475,2476,2477,2481,2483,2484,2485,2490,3024,3787,3864,4241,4763,4954,5725,6755,6785,7000,7003,7011,7012,7013,7014,7015,7016,7017,7018,7019,7041,7672,7751,7854,8000,8019,8043,8044,8045,8051,8052,8054,8055,8056,8065,8071,8073,8081,8082,8083,8086,8087,8088,8089,8092,8095,8096,8097,8111,8112,8121,8124,8125,8126,8127,8128,8130,8131,8132,8133,8135,8136,8137,8138,8139,8141,8143,8144,8145,8146,8151,8153,8154,8155,8156,8286,8558,9681],'BZ':[2073,2132,2162,2316,2463,2535,2612,2624,2699,2822,2840,2921,3154,3243,3327,3328,3400,3413,3416,3417,3418,3421,3423,3424,3425,3431,3432,3433,3434,3441,3442,3443,3444,3450,3458,3459,3461,3463,3464,3465,3466,3467,3500,3501,3508,3510,3515,3516,3517,3518,3519,3521,3524,3525,3526,3527,3528,3529,3530,3531,3532,3533,3534,3535,3552,3553,3554,3555,3556,3559,3561,3563,3564,3565,3571,3572,3573,3575,3576,3578,3579,3580,3586,3587,3588,3589,3592,3593,3594,3595,3596,3597,3598,3599,3608,3626,3635,3636,3641,3642,3643,3644,3645,3646,3648,3652,3653,3654,3655,3656,3657,3659,3663,3664,3671,3700,3704,3711,3712,3713,3714,3715,3717,3718,3720,3722,3723,3724,3726,3728,3729,3733,3735,3741,3742,3743,3744,3751,3752,3753,3754,3755,3756,3758,3759,3761,3768,3770,3773,3776,3777,3778,3779,3780,3783,3786,3791,3792,3793,3796,3800,3809,3811,3814,3815,3816,3817,3825,3826,3831,3832,3833,3834,3836,3837,3841,3842,3843,3844,3846,3847,3848,3851,3852,3853,3854,3855,3860,3863,3865,3866,3871,3873,3875,3876,3881,3882,3887,3888,3891,3892,3896,3897,3898,3899,3900,3903,3904,3907,3908,3909,3915,3916,3917,3918,3921,3922,3923,3924,3925,3926,3927,3928,3929,3931,3932,3935,3936,3942,3943,3944,3945,3950,3952,3955,3956,3957,3958,3959,3961,3962,3963,3964,3965,3967,3971,3972,3973,3974,3976,3977,3978,3980,3985,3987,3988,3989,3991,3993,3996,3997,4075,4114,4133,4622,4826,4914,5222,5440,5900,5903,5904,5905,6795,7045,7052,7083,7150,7158,7187,7228,7281,7305,7370,7383,7439,7681,7682,7716,7973,8074,8157,8458,8644,8695,8719,8876,8895,8896,9061,9071,9073,9134,9152,9184,9464,9682,9719,9736,9773],'SO':[2083,2151,2521,2625,2855,2903,3016,3348,3384,3388,3411,3562,3577,3603,3734,3769,3813,3821,3893,3894,4177,4231,4373,4565,5008,5212,5556,6341,7086,7191,7253,7255,7256,7258,7261,7271,7274,7275,7276,7279,7282,7284,7286,7394,7400,7431,7432,7434,7435,7436,7441,7442,7443,7444,7452,7453,7454,7456,7457,7458,7463,7464,7465,7471,7472,7473,7474,7476,7477,7478,7479,7500,7512,7513,7514,7515,7516,7521,7522,7523,7525,7527,7530,7532,7533,7535,7536,7538,7542,7543,7544,7551,7552,7555,7556,7557,7561,7562,7563,7564,7570,7582,7584,7585,7587,7588,7589,7918,7921,7922,7924,7976,7979,7987,7988,8041,8455,8495,8600,8609,8611,8612,8613,8614,8618,8621,8622,8623,8624,8626,8628,8630,8636,8637,8638,8646,8647,8648,8649,8651,8652,8653,8654,8656,8658,8660,8666,8667,8668,8671,8672,8673,8674,8675,8676,8681,8683,8684,8685,8691,8692,8693,8694,8696,8698,8699,8705,8706,8707,8708,8709,8710,8711,8712,8713,8714,8717,8718,8721,8722,8723,8724,8725,8726,8728,8731,8732,8733,8735,8737,8738,8739,8840,8849,8851,8853,8865,9131,9167,9676,9740,9935],'VE':[2096,2131,2243,3022,3066,3176,3345,3600,3621,3662,4275,4623,4755,4941,5000,5200,5449,5743,5919,6230,6445,6648,6912,7227,7541,7728,7756,7960,8085,8100,8103,8104,8105,8109,8142,8163,8164,8171,8172,8174,8175,8181,8182,8183,8184,8192,8194,8195,8196,8200,8220,8225,8226,8227,8228,8229,8230,8233,8236,8237,8241,8242,8243,8245,8247,8248,8251,8252,8253,8254,8255,8256,8257,8258,8261,8262,8263,8264,8265,8271,8272,8275,8281,8282,8283,8284,8291,8294,8295,8296,8297,8300,8308,8311,8312,8318,8319,8344,8345,8347,8348,8349,8352,8400,8411,8412,8413,8415,8416,8417,8418,8419,8420,8422,8423,8424,8427,8430,8431,8432,8433,8435,8438,8439,8440,8441,8442,8443,8444,8445,8446,8447,8448,8449,8451,8452,8454,8456,8457,8460,8469,8471,8473,8474,8476,8478,8481,8482,8483,8484,8485,8491,8492,8493,8494,8496,8497,8500,8511,8512,8513,8514,8515,8516,8517,8518,8521,8522,8523,8531,8532,8533,8541,8542,8543,8551,8552,8554,8555,8556,8557,8561,8562,8564,8565,8571,8572,8581,8582,8591,8592,8593,8594,8596,8597,8598,8697,8700,8858,9147,9451,9533,9534,9535,9913],'JN':[2133,2134,3651,5051,5052,5053,5054,5055,5061,5063,5064,5071,5081,5082,5083,5084,5085,5092,5093,5094,5095,5100,5111,5121,5122,5123,5124,5126,5130,5135,5136,5137,5141,5142,5143,5144,5152,5211,5213,5231,5232,5233,5234,5235,5241,5243,5244,5300,5309,5310,5321,5322,5323,5331,5340,5349,5350,5358,5359,5361,5362,5363,5400,5411,5412,5430,5435,5452,5453,5461,5462,5463,5464,5465,5471,5472,5474,5475,5476,5746,6043,7038,7130,7675,7741,7755,8161,8796,8864,8877],'GS':[2141,2230,2853,3153,3941,3994,4150,4943,4955,6115,6132,6769,6794,7257,7352,7742,7900,7914,8123,8897,9000,9011,9012,9021,9022,9023,9024,9025,9026,9027,9028,9029,9030,9062,9063,9072,9074,9081,9082,9083,9084,9085,9088,9089,9090,9091,9092,9093,9094,9095,9096,9097,9098,9099,9100,9111,9121,9122,9123,9124,9125,9126,9127,9132,9133,9135,9136,9141,9142,9143,9145,9146,9151,9154,9155,9161,9162,9163,9164,9165,9168,9169,9171,9172,9174,9175,9176,9177,9178,9181,9183,9200,9211,9221,9222,9223,9224,9225,9226,9228,9231,9232,9233,9234,9241,9242,9243,9244,9311,9312,9313,9314,9315,9316,9317,9321,9322,9323,9326,9327,9330,9339,9341,9342,9343,9344,9345,9346,9351,9352,9353,9361,9362,9363,9364,9365,9371,9372,9374,9375,9400,9407,9408,9421,9422,9423,9431,9433,9434,9435,9436,9437,9441,9442,9443,9444,9461,9462,9463,9471,9472,9473,9474,9475,9476,9481,9482,9483,9484,9485,9491,9492,9493,9494,9933],'SZ':[2166,2244,2464,2528,2900,3326,3627,3658,3767,4136,4138,4232,4233,4234,4235,4244,4245,4246,4266,4267,4300,4311,4320,4324,4325,4326,4327,4331,4332,4333,4334,4335,4337,4338,4341,4342,4343,4351,4352,4353,4354,4355,4356,4361,4362,4363,4371,4372,4374,4375,4376,4400,4405,4412,4431,4432,4433,4434,4440,4445,4446,4447,4450,4455,4456,4461,4463,4464,4465,4466,4467,4468,4471,4472,4474,4475,4481,4483,4484,4485,4486,4487,4488,4491,4493,4494,4496,4501,4502,4503,4511,4515,4516,4517,4521,4522,4523,4524,4525,4531,4532,4533,4534,4535,4536,4537,4541,4542,4543,4544,4546,4547,4551,4552,4553,4554,4555,4556,4557,4561,4562,4563,4564,4566,4567,4600,4611,4621,4624,4625,4627,4628,4631,4632,4633,4634,4635,4641,4643,4644,4645,4646,4700,4721,4722,4731,4732,4734,4735,4737,4741,4742,4743,4745,4746,4752,4754,4756,4762,4764,4765,4766,4767,4800,4803,4804,4811,4812,4813,4822,4823,4824,4831,4832,4833,4834,4835,4841,4842,4843,4844,4845,4900,4911,4912,4913,4921,4922,4931,4932,4933,4934,4935,4936,4937,4944,4945,4946,4947,4948,4951,4953,4956,4961,4962,4963,4966,4967,4968,4969,4972,4973,4974,4975,4976,4977,5700,7087,7143,7745,7811,7824,8191,8244,8425,8426,8734,9113,9512,9749,9824,9943,9954],'NO':[2175,2176,2177,2611,2616,2617,2618,2619,2640,2641,2642,2643,2645,2646,2647,2648,2649,2651,2652,2653,2655,2656,2659,2660,2668,2669,2672,2673,2675,2676,2677,2678,2682,2685,2686,2687,2688,2691,2692,2693,2694,2696,2697,3034,3041,3042,3043,3044,3045,3046,3047,3051,3052,3053,3060,3063,3065,3067,3068,3069,3070,3073,3074,3075,3077,3078,3082,3100,3102,3104,3109,3121,3123,3124,3125,3126,3127,3128,3129,3132,3133,3134,3135,3136,3137,3138,3141,3142,3143,3144,3145,3146,3147,3151,3152,3155,3161,3162,3163,3165,3170,3175,3177,3178,3179,3181,3182,3183,3184,3185,3186,3187,3188,3625,3812,3872,3934,3999,4492,7226,7700,7735,8434,9153,9495,9675],'HE':[2200,2377,2658,3000,3011,3013,3015,3023,3031,3032,3035,3036,3200,3213,3214,3231,3232,3233,3234,3235,3240,3242,3244,3245,3246,3247,3248,3250,3252,3253,3254,3255,3256,3257,3258,3259,3261,3262,3264,3265,3271,3272,3273,3274,3275,3281,3282,3283,3284,3291,3292,3293,3294,3295,3296,3300,3304,3321,3322,3323,3324,3325,3331,3332,3334,3336,3337,3341,3343,3344,3346,3347,3349,3350,3351,3352,3353,3354,3355,3357,3358,3359,3360,3369,3371,3373,3375,3377,3378,3379,3381,3382,3383,3385,3386,3387,3390,3394,3395,3396,3397,3398,3399,3414,3557,5516,5745,5836,6200,6758,7064,7132,7142,7539,7545,7720,7972,8553,8878,9723],'BA':[2309,2364,2654,2750,2851,2896,3021,3064,3131,3211,3221,3335,3426,3731,3763,3764,3895,4071,4642,5420,5534,5553,6134,6346,6612,6646,6767,6923,7094,7273,7283,7304,7331,7332,7333,7342,7343,7344,7345,7346,7347,7348,7349,7351,7381,7384,7391,7393,7396,7455,7537,7553,7586,7600,7621,7622,7623,7624,7625,7626,7627,7628,7629,7630,7631,7632,7633,7634,7635,7636,7661,7663,7664,7666,7671,7677,7683,7691,7693,7696,7711,7712,7714,7715,7717,7718,7723,7724,7725,7726,7727,7731,7732,7733,7737,7743,7744,7747,7752,7753,7757,7759,7762,7766,7768,7771,7772,7773,7774,7775,7781,7782,7783,7784,7785,7812,7813,7814,7815,7817,7818,7822,7823,7826,7827,7831,7834,7837,7838,7839,7843,7846,7849,7850,7851,7853,7912,7913,7915,7923,7925,7926,7934,7936,7951,7953,7958,7964,7967,7968,7971,7975,7980,7985,8042,8053,8134,8246,8273,8274,8342,8346,8414,8468,8617,8655,8744,9144,9245,9700],'TO':[2344,2657,2755,3012,3412,3884,3885,3937,3954,4117,4141,4495,4545,6090,7020,7026,7027,7030,7039,7042,7043,7044,7047,7051,7054,7056,7057,7061,7062,7063,7065,7066,7067,7068,7071,7072,7081,7082,7084,7085,7090,7091,7093,7095,7097,7098,7099,7100,7121,7122,7131,7133,7134,7135,7136,7139,7140,7144,7145,7146,7147,7148,7149,7159,7161,7162,7164,7171,7172,7173,7174,7175,7176,7181,7182,7183,7184,7185,7186,7192,7193,7194,7195,7211,7213,7214,7215,7224,7225,7251,7252,7341,7353,7354,7355,7356,7361,7362,7475,7841,7932,7937,7977,8060,8854,9019,9173],'BK':[2458,3368,3551,3591,4135,4336,6000,6008,6031,6032,6033,6034,6035,6041,6044,6045,6050,6055,6060,6062,6064,6065,6066,6067,6070,6075,6076,6078,6080,6085,6086,6087,6088,6096,6097,6098,6100,6111,6112,6113,6114,6116,6120,6131,6133,6136,6211,6221,6222,6223,6224,6236,6237,6238,6239,6300,6320,6323,6325,6326,6327,6328,6331,6333,6334,6336,6337,6342,6343,6344,6345,6347,6348,6351,6352,6353,6400,6411,6412,6413,6414,6421,6423,6424,6425,6430,6435,6440,6444,6446,6447,6448,6449,6451,6452,6453,6454,6455,6456,6500,6503,6511,6512,6513,6521,6522,6523,6524,6525,6527,6528,6782,7524,7526,7754,8162,8330,8409,9086,9112,9532],'HB':[3014,3849,4000,4002,4024,4025,4026,4027,4028,4029,4030,4031,4032,4033,4034,4060,4063,4064,4065,4066,4067,4069,4074,4078,4079,4080,4085,4086,4087,4090,4096,4097,4100,4103,4110,4115,4116,4118,4119,4121,4122,4123,4124,4125,4126,4127,4128,4130,4132,4134,4137,4142,4143,4144,4146,4161,4162,4163,4164,4171,4172,4173,4174,4175,4176,4181,4183,4184,4200,4211,4212,4220,4224,4225,4242,4243,4251,4252,4253,4254,4262,4263,4264,4271,4272,4273,4281,4283,4284,4285,4286,4287,4288,4482,4751,4964,6042,6235,6922,7694,7836,7940,8066,8627],'CS':[3033,3732,3794,3995,4952,5451,5665,5940,6135,6600,6621,6622,6623,6624,6625,6630,6635,6636,6645,6647,6700,6710,6720,6721,6722,6723,6724,6725,6726,6727,6728,6729,6750,6753,6754,6756,6757,6760,6762,6763,6764,6765,6768,6771,6772,6773,6774,6775,6783,6786,6787,6791,6792,6793,6800,6806,6821,6900,6903,6911,6913,6914,6915,6916,6917,6921,6931,6932,6933,7165,7800,8989],'BE':[3372,3661,3877,4558,4733,5500,5502,5510,5515,5520,5525,5526,5527,5530,5537,5538,5540,5551,5552,5555,5561,5600,5609,5621,5623,5630,5641,5643,5650,5661,5662,5663,5664,5666,5667,5668,5671,5672,5673,5674,5675,5703,5711,5712,5720,5726,5727,5731,5732,5734,5741,5742,5744,5747,5751,5752,5800,5811,5820,5830,5837,5838,5925,5931,5932,5945,5946,5948,6311,6335,6766,7673,8093,8152,8635,9373,9513,9730,9914]};


const vp_woo_pont_state_coordinates = {
	'BU': [47.4894, 19.1041],
	'PE': [47.3833, 19.4404],
	'VA': [47.1312, 16.8118],
	'BK': [46.6076, 19.3895],
	'BE': [46.6908, 21.0699],
	'BA': [46.0688, 18.2382],
	'BZ': [48.2170, 21.0209],
	'CS': [46.4470, 20.2191],
	'FE': [47.1779, 18.5847],
	'GS': [47.6404, 17.3863],
	'HB': [47.4835, 21.6140],
	'HE': [47.8131, 20.1920],
	'JN': [47.2266, 20.5014],
	'KE': [47.6198, 18.3420],
	'NO': [48.0130, 19.5863],
	'SO': [46.4838, 17.6299],
	'SZ': [48.0260, 22.1060],
	'TO': [46.5258, 18.5796],
	'VE': [47.1683, 17.7088],
	'ZA': [46.6823, 16.9070]
};
const vp_woo_pont_country_coordinates = {
	'CZ': [49.8509, 15.6689],
	'SK': [48.8394, 19.8575],
	'RO': [45.9280, 24.8392],
	'PL': [52.1721, 19.3782]
}

module.exports = {
	vp_woo_pont_state_postcodes,
    vp_woo_pont_state_coordinates,
    vp_woo_pont_country_coordinates,
};